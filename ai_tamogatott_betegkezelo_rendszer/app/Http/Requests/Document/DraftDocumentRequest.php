<?php

namespace App\Http\Requests\Document;

use App\Http\Requests\BaseRequest;
use App\Models\Document;

class DraftDocumentRequest extends BaseRequest
{

    public function rules(): array
    {
        return [
            'patient_id' => 'required|integer|exists:patients,id',
            'type' => 'required|in:' . implode(',', Document::TYPES),
            'hint' => 'nullable|string|max:255',
        ];
    }
}
