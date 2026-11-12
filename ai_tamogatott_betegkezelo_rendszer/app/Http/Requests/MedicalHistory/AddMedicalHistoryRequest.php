<?php

namespace App\Http\Requests\MedicalHistory;

use App\Http\Requests\BaseRequest;
use App\Models\MedicalHistory;

class AddMedicalHistoryRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'type' => 'required|in:' . implode(',', MedicalHistory::TYPES),
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'occurred_at' => 'nullable|date|before_or_equal:today',
        ];
    }
}
