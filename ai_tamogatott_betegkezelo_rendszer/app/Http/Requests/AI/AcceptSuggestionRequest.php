<?php

namespace App\Http\Requests\AI;

use App\Http\Requests\BaseRequest;
use App\Models\AiAnalysis;
use Illuminate\Validation\Validator;

class AcceptSuggestionRequest extends BaseRequest
{

    public function rules(): array
    {
        return [
            'source' => 'required|in:ai,manual',
            'selected_rank' => 'required_if:source,ai|nullable|integer|min:1',
            'custom_diagnosis_name' => 'required_if:source,manual|nullable|string|max:255',
            'summary' => 'required|string|max:4000',
            'recommendation' => 'required|string|max:4000',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->input('source') !== 'ai') {
                    return;
                }

                $analysis = $this->route('aiAnalysis');
                if (!$analysis instanceof AiAnalysis) {
                    return;
                }
                $count = count($analysis->suggestions ?? []);
                $rank = (int)$this->input('selected_rank');
                if ($rank < 1 || $rank > $count) {
                    $validator->errors()->add('selected_rank', 'A kiválasztott javaslat nem tartozik ehhez az elemzéshez.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'custom_diagnosis_name.required_if' => 'Kézi megadásnál a diagnózis nevét kötelező kitölteni.',
        ];
    }
}
