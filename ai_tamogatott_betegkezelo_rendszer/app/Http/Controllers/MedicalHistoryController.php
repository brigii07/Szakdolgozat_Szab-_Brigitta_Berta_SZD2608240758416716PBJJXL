<?php

namespace App\Http\Controllers;

use App\Http\Requests\MedicalHistory\AddMedicalHistoryRequest;
use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Services\MedicalHistoryService;
use Illuminate\Http\Request;

class MedicalHistoryController extends Controller
{
    public function __construct(private readonly MedicalHistoryService $historyService)
    {
    }

    public function addHistory(AddMedicalHistoryRequest $request, Patient $patient)
    {
        $this->historyService->addHistory($patient, $request->validated(), $request->user());
        return redirect()->route('patients.show', $patient)
            ->with('success', 'Előzmény rögzítve.')
            ->with('tab', 'history');
    }

    public function deleteHistory(Request $request, Patient $patient, MedicalHistory $medicalHistory)
    {
        abort_unless($request->user()->isDoctor() || $medicalHistory->recorded_by === $request->user()->id, 403);
        $this->historyService->deleteHistory($medicalHistory);
        return redirect()->route('patients.show', $patient)
            ->with('success', 'Előzmény törölve.')
            ->with('tab', 'history');
    }
}
