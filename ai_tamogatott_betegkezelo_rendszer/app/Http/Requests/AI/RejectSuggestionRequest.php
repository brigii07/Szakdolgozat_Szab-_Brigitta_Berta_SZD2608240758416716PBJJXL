<?php

namespace App\Http\Requests\AI;

use App\Http\Requests\BaseRequest;

class RejectSuggestionRequest extends BaseRequest
{

    public function rules(): array
    {
        return [
            'rejection_reason' => 'required|string|max:2000',
        ];
    }
}
