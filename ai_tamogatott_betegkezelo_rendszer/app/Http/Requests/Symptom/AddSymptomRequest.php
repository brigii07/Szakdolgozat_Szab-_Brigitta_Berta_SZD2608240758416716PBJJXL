<?php

namespace App\Http\Requests\Symptom;

use App\Http\Requests\BaseRequest;
use App\Models\Symptom;
use Illuminate\Validation\Validator;

class AddSymptomRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'symptom_ids' => 'array',
            'symptom_ids.*' => 'integer|exists:symptoms,id',
            'symptom_details' => 'array',
            'symptom_details.*' => 'nullable|string|max:255',
            'severity' => 'required|string|in:enyhe,közepes,súlyos',
            'other_symptom' => 'nullable|string|max:255',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $ids = collect($this->input('symptom_ids', []))->map(fn($id) => (int)$id);
                if ($ids->isEmpty()) {
                    return;
                }

                $symptoms = Symptom::whereIn('id', $ids)->whereNotNull('detail_type')->get();

                foreach ($symptoms as $symptom) {
                    $key = "symptom_details.{$symptom->id}";
                    $value = trim((string)$this->input($key, ''));

                    if ($value === '') {
                        $validator->errors()->add($key, "{$symptom->name}: a(z) \"{$symptom->detail_label}\" megadása kötelező.");
                        continue;
                    }

                    if ($symptom->detail_type === 'number') {
                        $number = str_replace(',', '.', $value);
                        $outOfRange = !is_numeric($number)
                            || ($symptom->detail_min !== null && (float)$number < $symptom->detail_min)
                            || ($symptom->detail_max !== null && (float)$number > $symptom->detail_max);

                        if ($outOfRange) {
                            $validator->errors()->add($key, "{$symptom->name}: érvényes értéket adjon meg ({$symptom->detail_min}–{$symptom->detail_max} {$symptom->detail_unit}).");
                        }
                    }
                }
            },
        ];
    }
}
