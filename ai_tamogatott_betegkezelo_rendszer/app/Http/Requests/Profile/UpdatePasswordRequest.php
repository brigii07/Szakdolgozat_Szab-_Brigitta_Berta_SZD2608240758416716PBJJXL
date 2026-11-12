<?php

namespace App\Http\Requests\Profile;

use App\Http\Requests\BaseRequest;

class UpdatePasswordRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ];
    }
}
