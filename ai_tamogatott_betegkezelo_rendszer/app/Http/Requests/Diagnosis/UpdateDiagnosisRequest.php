<?php

namespace App\Http\Requests\Diagnosis;

use App\Http\Requests\BaseRequest;


class UpdateDiagnosisRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'diagnosis' => 'required|string|max:255',
            'description' => 'required|string',
        ];
    }
}
