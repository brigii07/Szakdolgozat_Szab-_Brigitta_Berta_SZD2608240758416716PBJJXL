<?php

namespace App\Http\Requests\Bloodtest;

use App\Http\Requests\BaseRequest;

class ExtractBloodtestRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
        ];
    }
}
