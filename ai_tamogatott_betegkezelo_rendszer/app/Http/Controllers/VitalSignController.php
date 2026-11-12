<?php

namespace App\Http\Controllers;

use App\Http\Requests\VitalSign\AddVitalSignRequest;
use App\Models\Patient;
use App\Models\VitalSign;
use App\Services\VitalSignService;
use Illuminate\Http\Request;

class VitalSignController extends Controller
{
    public function __construct(private readonly VitalSignService $vitalSignService)
    {
    }

    public function addVitalSign(AddVitalSignRequest $request, Patient $patient)
    {
        $this->vitalSignService->addVitalSign($patient, $request->validated(), $request->user());
        return redirect()->route('patients.show', $patient)
            ->with('success', 'Mérés rögzítve.')
            ->with('tab', 'vitals');
    }

    public function deleteVitalSign(Request $request, Patient $patient, VitalSign $vitalSign)
    {
        abort_unless($request->user()->isDoctor() || $vitalSign->recorded_by === $request->user()->id, 403);
        $this->vitalSignService->deleteVitalSign($vitalSign);
        return redirect()->route('patients.show', $patient)
            ->with('success', 'Mérés törölve.')
            ->with('tab', 'vitals');
    }
}
