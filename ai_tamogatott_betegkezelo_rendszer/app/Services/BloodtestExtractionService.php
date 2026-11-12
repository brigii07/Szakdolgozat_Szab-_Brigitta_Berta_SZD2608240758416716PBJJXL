<?php

namespace App\Services;

use App\Services\Concerns\ResolvesAiModel;
use Illuminate\Http\UploadedFile;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\ValueObjects\Media\Document;
use Prism\Prism\ValueObjects\Media\Image;

class BloodtestExtractionService
{
    use ResolvesAiModel;

    public function extract(UploadedFile $file): array
    {
        $mime = $file->getMimeType();
        $media = $mime === 'application/pdf' ? Document::fromLocalPath($file->getRealPath(), $mime)
            : Image::fromLocalPath($file->getRealPath(), $mime);

        $schema = new ObjectSchema(
            name: 'bloodtest_extraction',
            description: 'A laborleletről kiolvasott vérkép-paraméterek.',
            properties: [
                new ArraySchema(
                    name: 'results',
                    description: 'A leleten szereplő összes laborparaméter, a leleten látható sorrendben.',
                    items: new ObjectSchema(
                        name: 'result',
                        description: 'Egy laborparaméter egy sora',
                        properties: [
                            new StringSchema('parameter_name', 'A paraméter neve pontosan úgy, ahogyan a leleten szerepel'),
                            new StringSchema('value', 'A mért érték számként, tizedesponttal, mértékegység nélkül'),
                            new StringSchema('unit', 'Mértékegység a leletről; üres szöveg, ha nincs feltüntetve'),
                            new StringSchema('reference_min', 'A referenciatartomány alsó határa; üres szöveg ha nem olvasható le'),
                            new StringSchema('reference_max', 'A referenciatartomány felső határa; üres szöveg ha nem olvasható le'),
                            new StringSchema('uncertain', 'Pontosan "igen" vagy "nem": bizonytalan-e a sor bármely adata'),
                        ],
                        requiredFields: ['parameter_name', 'value', 'unit', 'reference_min', 'reference_max', 'uncertain'],
                    ),
                ),
                new StringSchema('notes', 'Rövid megjegyzés az olvashatóságról vagy hiányzó adatokról; üres szöveg, ha nincs'),
            ],
            requiredFields: ['results', 'notes'],
        );

        $prompt = <<<PROMPT
        A csatolt fájl egy laboratóriumi lelet (vérkép). Olvasd ki belőle az összes laborparamétert.
        Szabályok:
        - Csak azt írd le, ami ténylegesen látható a leleten. Semmit ne találj ki és ne becsülj.
        - Ha egy érték nem olvasható biztosan, a sort jelölt uncertain = true értékkel.
        - Tizedesvessző helyett tizedespontot használj.
        - "<5" alakú referencia: reference_min = 0, reference_max = 5. ">60" alakú: reference_min = 60, reference_max = null.
        - Ha a referenciatartomány nem szerepel, mindkét határ legyen null.
        - Ha a fájl nem laborletet, adj vissza üres results listát, és a notes mezőben írd le, mit látsz.
        - Ne értelmezd orvosilag az eredményeket.
        PROMPT;

        $response = Prism::structured()
            ->using($this->resolveProvider(), $this->resolveModel())
            ->withClientOptions(['timeout' => 90])
            ->withSchema($schema)
            ->withSystemPrompt('Pontos adatrögzítő asszisztens vagy egy magyar egészségügyi rendszerben. Laborleletekről olvasol ki adatokat, magyar nyelven válaszolsz.')
            ->withPrompt($prompt, [$media])
            ->asStructured();

        $results = collect($response->structured['results'] ?? [])
            ->filter(fn($r) => filled($r['parameter_name'] ?? null) && is_numeric($r['value'] ?? null))
            ->map(fn($r) => [
                'parameter_name' => trim($r['parameter_name']),
                'value' => $this->formatNumber($r['value']),
                'unit' => trim((string)($r['unit'] ?? '')),
                'reference_min' => $this->formatNumber($r['reference_min'] ?? null),
                'reference_max' => $this->formatNumber($r['reference_max'] ?? null),
                'uncertain' => strtolower(trim((string)($r['uncertain'] ?? ''))) === 'igen',
            ])
            ->values()
            ->all();

        return [
            'results' => $results,
            'notes' => trim((string)($response->structured['notes'] ?? '')),
        ];
    }

    private function formatNumber(mixed $number): string
    {
        if (!is_numeric($number)) {
            return '';
        }
        return rtrim(rtrim(number_format((float)$number, 4, '.', ''), '0'), '.');
    }
}
