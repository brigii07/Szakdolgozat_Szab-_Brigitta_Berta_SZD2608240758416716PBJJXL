<?php

namespace App\Services\Concerns;

use InvalidArgumentException;
use Prism\Prism\Enums\Provider;

trait ResolvesAiModel
{
    protected function resolveProvider(): Provider
    {
        $provider = strtolower(trim((string)config('services.ai_diagnosis.provider', 'gemini')));
        return match ($provider) {
            'gemini' => Provider::Gemini,
            'openai' => Provider::OpenAI,
            'anthropic' => Provider::Anthropic,
            default => throw new InvalidArgumentException(
                "Ismeretlen AI_DIAGNOSIS_PROVIDER érték: '{$provider}' . " . 'Használható értékek: gemini, openai, anthropic.'
            )
        };
    }

    protected function resolveModel(): string
    {
        $model = trim((string)config('services.ai_diagnosis.model', ''));

        if ($model === '') {
            throw new InvalidArgumentException(
                'Az AI_DIAGNOSIS_MODEL beállítás üres. Adja meg a használni kívánt modell azonosítóját.'
            );
        }

        return $model;
    }
}
