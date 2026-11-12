<?php

namespace App\Http\Requests\BloodTest;

use App\Http\Requests\BaseRequest;

class AddBloodtestResultsRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'results' => 'required|array|min:1|max:60',
            'results.*.parameter_name' => 'required|string|max:100',
            'results.*.value' => 'required|numeric',
            'results.*.unit' => 'required|string|max:50',
            'results.*.reference_min' => 'nullable|numeric',
            'results.*.reference_max' => 'nullable|numeric|gte:results.*.reference_min',
            'source' => 'nullable|in:manual,ai_import',
            'verified' => 'required_if:source,ai_import|accepted_if:source,ai_import',
        ];
    }

    public function messages(): array
    {
        return [
            'verified.required_if' => 'AI-beolvasás mentése előtt nyugtázni kell, hogy ellenőrizte az adatokat.',
            'verified.accepted_if' => 'AI-beolvasás mentése előtt nyugtázni kell, hogy ellenőrizte az adatokat.',
            'results.*.reference_max.gte' => 'A referenciatartomány felső határa nem lehet kisebb az alsónál.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $results = collect($this->input('results', []))->map(function ($row) {
            foreach (['value', 'reference_min', 'reference_max'] as $field) {
                if (isset($row[$field]) && is_string($row[$field])) {
                    $row[$field] = str_replace([',', ' '], ['.', ''], trim($row[$field]));
                    if ($row[$field] === '') {
                        $row[$field] = null;
                    }
                }
            }
            return $row;
        })->all();
        $this->merge(['results' => $results]);
    }
}
