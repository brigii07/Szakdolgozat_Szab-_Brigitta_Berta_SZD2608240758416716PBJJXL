<?php

namespace App\Http\Requests\Patient;

use App\Http\Requests\BaseRequest;

class AddPatientRequest extends BaseRequest
{

    public function rules(): array
    {
        return [
            'taj_number' => 'required|digits:9|unique:patients,taj_number',
            'name' => 'required|string|max:255',
            'birth_date' => 'required|date_format:Y.m.d',
            'birth_place' => 'required|string|max:255',
            'gender' => 'required|string|in:M,F,Other',
            'phone' => 'required|string|max:50',
            'email' => 'required|email|unique:patients,email',
            'address' => 'required|string|max:255',
            'is_urgent' => 'sometimes|boolean',
        ];
    }
}
