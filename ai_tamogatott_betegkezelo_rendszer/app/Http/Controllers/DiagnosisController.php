<?php

namespace App\Http\Controllers;

use App\Http\Requests\Diagnosis\AddDiagnosisRequest;
use App\Http\Requests\Diagnosis\UpdateDiagnosisRequest;
use App\Models\Diagnosis;
use App\Models\Patient;
use App\Services\DiagnosisService;

class DiagnosisController extends Controller
{
    public function __construct(private readonly DiagnosisService $diagnosisService)
    {
    }

    public function addDiagnosis(AddDiagnosisRequest $request, Patient $patient)
    {
        $this->authorize('manage-diagnosis');
        $this->diagnosisService->addDiagnosis($patient, $request->validated(), $request->user());
        return redirect()->route('patients.show', $patient)->with('success', 'Diagnózis elmentve.');
    }

    public function updateDiagnosis(UpdateDiagnosisRequest $request, Patient $patient, Diagnosis $diagnosis)
    {
        $this->authorize('manage-diagnosis');
        $this->diagnosisService->updateDiagnosis($patient, $diagnosis, $request->validated());
        return redirect()->route('patients.show', $patient)->with('success', 'Diagnózis frissítve.');
    }
}
