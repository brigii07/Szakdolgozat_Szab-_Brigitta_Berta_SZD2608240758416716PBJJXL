<?php

namespace App\Http\Requests\VitalSign;

use App\Http\Requests\BaseRequest;
use App\Models\VitalSign;
use Illuminate\Validation\Validator;

class AddVitalSignRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'systolic' => 'nullable|integer|between:50,260|required_with:diastolic',
            'diastolic' => 'nullable|integer|between:30,160|required_with:systolic',
            'pulse' => 'nullable|integer|between:20,250',
            'temperature' => 'nullable|numeric|between:30,45',
            'spo2' => 'nullable|integer|between:50,100',
            'weight' => 'nullable|numeric|between:0.5,400',
            'height' => 'nullable|integer|between:30,250',
            'measured_at' => 'nullable|date|before_or_equal:now',
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['temperature', 'weight'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => str_replace(',', '.', $this->input($field))]);
            }
        }
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $anyFilled = collect(array_keys(VitalSign::PARAMETERS))
                    ->contains(fn($field) => filled($this->input($field)));
                if (!$anyFilled) {
                    $validator->errors()->add('systolic', 'Legalább egy mért értéket adjon meg.');
                }
                if (filled($this->input('systolic')) && filled($this->input('diastolic'))
                    && (int)$this->input('diastolic') >= (int)$this->input('systolic')) {
                    $validator->errors()->add('diastolic', 'A diasztolés érték nem lehet nagyobb a szisztolésnél.');
                }
            },
        ];
    }
}
