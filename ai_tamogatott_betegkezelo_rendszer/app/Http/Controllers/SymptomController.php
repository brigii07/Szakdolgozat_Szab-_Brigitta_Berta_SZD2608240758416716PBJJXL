<?php

namespace App\Http\Controllers;

use App\Http\Requests\Symptom\AddSymptomRequest;
use App\Models\Patient;
use App\Services\SymptomService;

class SymptomController extends Controller
{
    public function __construct(private readonly SymptomService $symptomService)
    {
    }

    public function addSymptoms(AddSymptomRequest $request, Patient $patient)
    {
        $this->symptomService->addSymptoms($patient, $request->validated());
        return redirect()->route('patients.show', $patient)->with('success', 'Tünetek elmentve.')->with('tab', 'symptoms');
    }
}
