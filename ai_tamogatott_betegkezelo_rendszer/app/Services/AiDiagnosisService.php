<?php

namespace App\Services;

use App\Models\CareEpisode;
use App\Models\Diagnosis;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\NumberSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use App\Models\MedicalHistory;
use App\Services\Concerns\ResolvesAiModel;

class AiDiagnosisService
{
    use ResolvesAiModel;

    public function analyze(CareEpisode $episode): array
    {
        $patient = $episode->patient;
        $symptoms = $episode->symptoms()->get();
        $latestBloodTest = $episode->bloodtests()->with('results')->first();
        $latestVitals = $episode->vitalSigns()->first();

        $symptomNames = $symptoms->pluck('name')->all();
        $abnormalResults = $latestBloodTest ? $latestBloodTest->results->filter(fn($r) => $r->isOutOfRange())->pluck('parameter_name')->all()
            : [];

        $previousDiagnoses = $patient->diagnoses()
            ->where(fn($q) => $q->where('episode_id', '!=', $episode->id)->orWhereNull('episode_id'))
            ->latest('created_at')
            ->take(5)
            ->get();

        $latestNote = $patient->notes()->latest('created_at')->first();
        $suggestions = [];
        $error = false;

        if (!empty($symptomNames) || $latestBloodTest) {
            try {
                $suggestions = $this->requestSuggestions($episode, $symptoms, $latestBloodTest, $latestVitals, $previousDiagnoses, $latestNote);
            } catch (\Throwable $exception) {
                Log::error('AI diagnosztikai javaslat kérése sikertelen.', [
                    'patient_id' => $patient->id,
                    'episode_id' => $episode->id,
                    'message' => $exception->getMessage(),
                ]);
                $error = true;
            }
        }

        return [
            'suggestions' => $suggestions,
            'symptoms_used' => $symptomNames,
            'abnormal_results_used' => $abnormalResults,
            'error' => $error,
        ];
    }

    private function requestSuggestions(CareEpisode $episode, Collection $symptoms, $latestBloodtest, $latestVitals, Collection $previousDiagnoses, $latestNote): array
    {
        $patient = $episode->patient;
        $symptomsText = $symptoms
            ->map(function ($symptom) {
                $detail = $symptom->formatDetail($symptom->pivot->detail);
                return $symptom->name . ($detail ? " ({$symptom->detail_label}: {$detail})" : '')
                    . " - súlyosság: {$symptom->pivot->severity}";
            })
            ->implode(', ') ?: 'Nincs rögzített tünet.';

        $bloodtestText = 'Nincs rögzített vérkép.';
        $abnormalSummary = 'Nincs értékelhető laboradat.';
        if ($latestBloodtest) {
            $bloodtestDate = $latestBloodtest->created_at?->format('Y.m.d.');
            $rows = $latestBloodtest->results->map(fn($result) => $result->describeForAi())->implode("\n");
            $bloodtestText = "Lelet dátuma: {$bloodtestDate}\n{$rows}";

            $abnormal = $latestBloodtest->results->filter(fn($r) => $r->isOutOfRange());
            $abnormalSummary = $abnormal->isEmpty()
                ? 'A vérkép minden értéke a referenciatartományon belül van.'
                : $abnormal->map(fn($r) => "{$r->parameter_name} {$r->deviationDirection()} "
                    . "({$r->value} {$r->unit}, referencia: {$r->referenceLabel()})")->implode('; ');
        }

        $vitalsSection = $latestVitals ? "\n## Legutóbbi vitális paraméterek\n" . $latestVitals->describeForAi() . "\n"
            : '';

        $historyText = $patient->medicalHistories()->take(10)->get()
            ->map(fn(MedicalHistory $h) => '- ' . MedicalHistory::typeLabel($h->type) . ': ' . $h->title
                . ($h->occurred_at ? ' (' . $h->occurred_at->format('Y.m.d.') . ')' : '')
                . (filled($h->description) ? " - {$h->description}" : ''))
            ->implode("\n");
        $historySection = $historyText ? "\n## Kórelőzmény és állapotváltozások:\n{$historyText}\n" : '';

        $previousSection = '';
        if ($previousDiagnoses->isNotEmpty()) {
            $list = $previousDiagnoses
                ->map(fn(Diagnosis $d) => '- ' . $d->created_at?->format('Y.m.d.') . ': ' . $d->diagnosis)
                ->implode("\n");
            $previousSection = "\n## Korábbi, LEZÁRT ellátási esetek diagnózisai (előzmény, nem a jelen panasz)\n{$list}\n";
        }

        $noteSection = $latestNote ? "\n## Legutóbbi orvosi megjegyzés\n{$latestNote->note}\n" : '';

        $schema = new ObjectSchema(
            name: 'diagnosis_suggestions',
            description: 'Lehetséges betegségek listája a megadott tünetek és laboreredmények alapján.',
            properties: [
                new ArraySchema(
                    name: 'suggestions',
                    description: 'A lehetséges diagnózisok, csökkenő valószínűség szerint rendezve, legfeljebb 3 elem',
                    items: new ObjectSchema(
                        name: 'suggestion',
                        description: 'Egy lehetséges diagnózis',
                        properties: [
                            new StringSchema('name', 'A lehetséges betegség/diagnózis megnevezése, magyarul'),
                            new NumberSchema('confidence', 'Becsült megbízhatóság 0 és 100 között'),
                            new StringSchema('explanation', 'Szakmai magyarázat kb. 2-3 mondatban: mely tünetek, laborértékek, a korábbi diagnózis és/vagy az orvosi megjegyzés támasztja alá a javaslatot'),
                            new StringSchema('lab_consistency', 'Rövid, tényszerű értékelés arról, hogy a rögzített laborértékek alátámasztják vagy cáfolják ezt a diagnózist. Nevezd meg a konkrét paramétereket és értékeket. Ha nincs rögzített vérkép, írd azt, hogy "Nincs elérhető laboradat."'),
                        ],
                        requiredFields: ['name', 'confidence', 'explanation', 'lab_consistency'],
                    )
                ),
            ],
            requiredFields: ['suggestions']
        );

        $prompt = <<<PROMPT
        Az alábbi beteg adatai alapján adj lehetséges diagnózis-javaslatokat egy orvos számára, kizárólag döntéstámogatás céljából.
        Páciens: {$patient->age} éves, {$patient->genderLabel()}
        Rögzített tünetek: {$symptomsText}
        Legutóbbi vérkép (KÖTELEZŐEN FIGYELEMBE VEENDŐ): {$bloodtestText}
        A vérkép eltérései egy mondatban
        {$abnormalSummary}
        {$vitalsSection}{$previousSection}{$historySection}{$noteSection}
        Szabályok:
        - A "rögzített tünetek" kizárólag a jelenlegi ellátási eset panaszait tartalmazzák.
        A korábbi esetek diagnózisait előzményként vedd figyelembe, de ne tekintsd aktuális  tünetnek.
        - A laborértékeket tekintsd erős, objektív bizonyítéknak. Ne javasolj olyan betegséget, amelynek a
        jellemző laboreltérése ellentétes a fent rögzített értékekkel. Például, ha a hemoglobin és a
        vörösvértestszám normális vagy magas, ne javasolj vashiányos vérszegénységet.
        - Minden javaslatnál a lab_consistency mezőben nevezd meg a konkrét paramétereket és értékeket,
        amelyek alátámasztják vagy cáfolják a diagnózist.
        - Ha egy laborérték a referenciatartományon belül van, azt ne állítsd be kórosnak.
        - Ha a tünetek és a laborértékek együtt nem engednek meg három javaslatot, adj kevesebbet.
        - Ha egyáltalán nincs rögzített vérkép, ezt a magyarázatban jelezd, és a megbízhatóság ennek
        megfelelően legyen alacsonyabb.
        - Minden javaslathoz adj kb. 2-3 mondatos szakmai magyarázatot.
        - Adj vissza legfeljebb 3 lehetséges diagnózist, csökkenő valószínűség szerint rendezve.
        PROMPT;

        $response = Prism::structured()
            ->using($this->resolveProvider(), $this->resolveModel())
            ->withSchema($schema)
            ->usingTemperature(0.2)
            ->withClientOptions(['timeout' => 90])
            ->withSystemPrompt(
                'Te egy orvosi döntéstámogató mesterséges intelligencia vagy egy magyar egészségügyi '
                . 'rendszerben. Kizárólag a megadott tünetek, laboreredmények, vitális paraméterek, valamint - ha rendelkezésre '
                . 'áll - az előzmények és az orvosi megjegyzés alapján adj javaslatot. '
                . 'Sose fogalmazz úgy, mintha biztos diagnózist adnál - a végső döntés mindig az orvosé. '
                . 'A válaszaid legyenek magyar nyelvűek, szakmailag pontosak, a magyarázat javaslatonként kb. 2-3 mondat.'
                . 'A rögzített laboreredmények objektív adatok: sose mondj ellent nekik, és sose feltételezz olyan '
                . 'laboreltérést, ami nem szerepel a megadott adatok között.'
            )
            ->withPrompt($prompt)
            ->asStructured();

        $suggestions = $response->structured['suggestions'] ?? [];

        return array_map(function (array $suggestion) {
            $suggestion['confidence'] = (int)round($suggestion['confidence'] ?? 0);
            return $suggestion;
        }, $suggestions);
    }
}
