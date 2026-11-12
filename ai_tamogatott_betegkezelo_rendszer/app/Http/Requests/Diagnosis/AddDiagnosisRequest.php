<?php

namespace App\Http\Requests\Diagnosis;

use App\Http\Requests\BaseRequest;

class AddDiagnosisRequest extends BaseRequest
{

    public function rules(): array
    {
        return [
            'diagnosis' => 'required|string',
            'description' => 'required|string',
        ];
    }
}
