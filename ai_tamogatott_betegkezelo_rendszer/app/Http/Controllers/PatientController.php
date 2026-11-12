<?php

namespace App\Http\Controllers;

use App\Http\Requests\Patient\AddPatientRequest;
use App\Http\Requests\Patient\UpdatePatientRequest;
use App\Models\Patient;
use App\Models\PatientAccessLog;
use App\Services\PatientAccessLogger;
use App\Services\PatientService;
use App\Services\PatientSummaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PatientController extends Controller
{
    public function __construct(private readonly PatientService $patientService)
    {
    }

    public function patientIndex(Request $request)
    {
        $patients = $this->patientService->listPatient($request);
        return view('patients.index', compact('patients'));
    }

    public function createPatient()
    {
        return view('patients.create');
    }

    public function addPatient(AddPatientRequest $request)
    {
        $patient = $this->patientService->addPatient($request->validated(), $request->user());
        return redirect()->route('patients.show', $patient)->with('success', 'A páciens sikeresen rögzítve.');
    }

    public function showPatient(Request $request, Patient $patient, PatientAccessLogger $accessLogger)
    {
        $episode = null;
        if ($request->filled('episode')) {
            $episode = $patient->episodes()
                ->whereKey($request->integer('episode'))
                ->firstOrFail();
        }
        $accessLogger->record($patient, PatientAccessLog::ACTION_VIEW);
        return view('patients.show', $this->patientService->detailsPatient($patient, $episode));
    }

    public function editPatient(Patient $patient)
    {
        return view('patients.edit', compact('patient'));
    }

    public function updatePatient(UpdatePatientRequest $request, Patient $patient)
    {
        $this->patientService->updatePatient($patient, $request->validated());
        return redirect()->route('patients.show', $patient)->with('success', 'A páciens adatai frissítve.');
    }

    public function deletePatient(Patient $patient)
    {
        $this->patientService->deletePatient($patient);
        return redirect()->route('patients.index')->with('success', 'A páciens törölve.');
    }

    public function downloadSummary(Request $request, Patient $patient, PatientSummaryService $summaryService, PatientAccessLogger $accessLogger)
    {
        $this->authorize('view-full-history');
        $accessLogger->record($patient, PatientAccessLog::ACTION_SUMMARY_PDF);
        $fileName = 'osszefoglalo-' . Str::slug($patient->name) . '-' . now()->format('Ymd') . '.pdf';
        return $summaryService->makePdf($patient, $request->user())->download($fileName);
    }
}
