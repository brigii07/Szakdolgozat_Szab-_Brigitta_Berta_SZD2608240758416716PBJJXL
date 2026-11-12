<?php

namespace App\Services;

use App\Models\CareEpisode;
use App\Models\Document;
use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Services\Concerns\ResolvesAiModel;
use Prism\Prism\Facades\Prism;

class DocumentDraftService
{
    use ResolvesAiModel;

    private const TYPE_INSTRUCTIONS = [
        Document::TYPE_CERTIFICATE => 'Orvosi igazolás törzsszövege: rögzítse, hogy a páciens orvosi vizsgálaton vett részt, és a diagnózis alapján milyen kímélet vagy távolmaradás indokolt. Rövid, hivatalos hangvétel, 2-4 mondat.',
        Document::TYPE_REFERRAL => 'Szakorvosi beutaló törzsszövege: a beutalás indoka, a releváns panaszok és eltérő leletek tömör összefoglalása, végül a konkrét kérés a szakorvos felé (pl. "Kérem szíves kivizsgálását és véleményét."). 4-8 mondat.',
        Document::TYPE_REPORT => 'Orvosi lelet törzsszövege: a panaszok, a vizsgálati eredmények (vitális paraméterek, laboreltérések) és a diagnózis tárgyilagos összefoglalása, majd a javasolt teendők. 5-10 mondat.',
        Document::TYPE_OTHER => 'Általános orvosi dokumentum törzsszövege a megadott adatok és az orvos által megadott cél alapján. 3-6 mondat.',
    ];

    public function draft(Patient $patient, string $type, ?string $hint): string
    {
        $instruction = self::TYPE_INSTRUCTIONS[$type] ?? self::TYPE_INSTRUCTIONS[Document::TYPE_OTHER];
        $hintSection = filled($hint) ? "\nAz orvos kiegészítése, a dokumentum célja: {$hint}\n" : '';
        $context = $this->buildContext($patient, $patient->episodes()->first());

        $prompt = <<<PROMPT
        Feladat: {$instruction}
        {$hintSection}
        A páciens adatai:
        {$context}

        Szabályok:
        - Csak a fenti adatokat használd. Semmit ne találj ki: se dátumot, se vizsgálatot, se gyógyszert, se értéket.
        - Ha egy szükséges adat hiányzik, szögletes zárójeles helykitöltőt írj helyette, pl. [időtartam] vagy [szakrendelés].
        - A páciens nevét, TAJ-számát és elérhetőségét ne írd bele, azok a dokumentum fejlécén szerepelnek.
        - Csak a törzsszöveget add vissza: cím, megszólítás, aláírás, felsorolásjel és Markdown formázás nélkül.
        PROMPT;

        $response = Prism::text()
            ->using($this->resolveProvider(), $this->resolveModel())
            ->usingTemperature(0.3)
            ->withClientOptions(['timeout' => 60])
            ->withSystemPrompt(
                'Tapasztalt magyar háziorvos vagy, aki hivatalos orvosi dokumentumokat fogalmaz. '
                . 'Tömören, szakszerűen, magyar orvosi szóhasználattal írsz. A szöveget az orvos ellenőrzi és véglegesíti.'
            )
            ->withPrompt($prompt)
            ->asText();

        return trim(str_replace(['**', '__', '#'], '', $response->text));
    }

    private function buildContext(Patient $patient, ?CareEpisode $episode): string
    {
        $lines = [];

        $lines[] = 'Életkor, nem: '
            . ($patient->age !== null ? "{$patient->age} éves" : 'ismeretlen életkorú')
            . ', ' . $patient->genderLabel();

        $diagnosis = $episode?->diagnoses()->latest('created_at')->first();
        $lines[] = 'Diagnózis: ' . ($diagnosis ? "{$diagnosis->diagnosis}" . (filled($diagnosis->description) ? " ({$diagnosis->description})" : '')
                : 'nincs rögzített diagnózis');

        $symptoms = $episode
            ? $episode->symptoms()->get()->map(function ($symptom) {
                $detail = $symptom->formatDetail($symptom->pivot->detail);
                return $symptom->name . ($detail ? " ({$detail})" : '') . ", {$symptom->pivot->severity}";
            })->implode('; ') : '';
        $lines[] = 'Panaszok: ' . ($symptoms ?: 'nincs rögzítve');

        $bloodtest = $episode?->bloodtests()->with('results')->first();
        if ($bloodtest) {
            $abnormal = $bloodtest->results->filter->isOutOfRange()
                ->map(fn($r) => "{$r->parameter_name} {$r->value} {$r->unit} ({$r->deviationDirection()}, ref.: {$r->reference_min}-{$r->reference_max})")
                ->implode('; ');
            $lines[] = 'Laborlelet (' . $bloodtest->created_at?->format('Y.m.d.') . '): '
                . ($abnormal ?: 'minden vizsgált érték a referenciatartományon belül');
        }

        $vitals = $episode?->vitalSigns()->first();
        if ($vitals) {
            $lines[] = 'Vitális paraméterek, ' . $vitals->describeForAi();
        }

        $history = $patient->medicalHistories()->take(5)->get()
            ->map(fn(MedicalHistory $h) => MedicalHistory::typeLabel($h->type) . ': ' . $h->title)
            ->implode('; ');
        if ($history) {
            $lines[] = 'Előzmények: ' . $history;
        }

        return implode("\n", $lines);
    }
}
