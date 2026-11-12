<?php

namespace App\Http\Requests\Note;

use App\Http\Requests\BaseRequest;

class AddNoteRequest extends BaseRequest
{

    public function rules(): array
    {
        return [
            'note' => 'required|string',
        ];
    }
}
