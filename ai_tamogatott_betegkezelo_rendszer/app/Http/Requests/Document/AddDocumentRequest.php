<?php

namespace App\Http\Requests\Document;

use App\Http\Requests\BaseRequest;
use App\Models\CareEpisode;
use App\Models\Document;
use App\Models\Patient;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;
use PhpParser\Comment\Doc;

class AddDocumentRequest extends BaseRequest
{

    public function rules(): array
    {
        return [
            'patient_id' => 'required|integer|exists:patients,id',
            'type' => 'required|in:' . implode(',', Document::TYPES),
            'note' => 'nullable|string|max:2000',
            'ai_drafted' => 'sometimes|boolean',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
            'incapacity_reason' => 'nullable|string|max:255',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $type = (string)$this->input('type');
                if (!Document::requiresDiagnosedPatient($type)) {
                    return;
                }

                $patient = Patient::find($this->input('patient_id'));
                if (!$patient) {
                    return;
                }

                $episode = $patient->episodes()->first();
                if (!$episode || $episode->status !== CareEpisode::STATUS_DIAGNOSED) {
                    $validator->errors()->add('patient_id',
                        $type . ' csak olyan páciensnek állítható ki, akinek a legutóbbi ellátási esete '
                        . 'diagnózissal lezárult. Beutaló, lelet és egyéb dokumentum bármely páciensnek kiállítható.');
                }
            },

            function (Validator $validator) {
                if (!Document::requiresPeriod((string)$this->input('type'))) {
                    return;
                }

                if (blank($this->input('valid_from')) || blank($this->input('valid_to'))) {
                    $validator->errors()->add('valid_from',
                        'Keresőképtelenségi igazoláshoz a kezdő és a záró dátum megadása kötelező.');
                    return;
                }

                if (blank($this->input('incapacity_reason'))) {
                    $validator->errors()->add('incapacity_reason', 'A keresőképtelenség jogcímének megadása kötelező.');
                } elseif (!in_array($this->input('incapacity_reason'), Document::INCAPACITY_REASONS, true)) {
                    $validator->errors()->add('incapacity_reason', 'Érvénytelen jogcím.');
                }

                $from = Carbon::parse($this->input('valid_from'));
                $to = Carbon::parse($this->input('valid_to'));

                if ($from->diffInDays($to) + 1 > 365) {
                    $validator->errors()->add('valid_to', 'A keresőképtelenség időtartama nem haladhatja meg az egy évet.');
                }

                if ($from->lt(now()->subYear())) {
                    $validator->errors()->add('valid_from',
                        'A keresőképtelenség kezdő dátuma nem lehet egy évnél régebbi.');
                }
            },
        ];
    }
}
