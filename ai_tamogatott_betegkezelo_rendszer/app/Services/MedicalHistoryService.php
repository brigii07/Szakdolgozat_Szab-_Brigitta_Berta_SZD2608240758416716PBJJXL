<?php

namespace App\Services;

use App\Models\MedicalHistory;
use App\Models\Patient;
use Illuminate\Contracts\Auth\Authenticatable;

class MedicalHistoryService
{
    public function addHistory(Patient $patient, array $data, Authenticatable $user): MedicalHistory
    {
        $history = $patient->medicalHistories()->create([
            ...$data,
            'recorded_by' => $user->id,
        ]);
        ActivityLogger::log(MedicalHistory::typeLabel($history->type) . ' rögzítve: ' . $history->title, $patient->id);
        return $history;
    }

    public function deleteHistory(MedicalHistory $history): void
    {
        $history->delete();
        ActivityLogger::log('Előzmény törölve: ' . $history->title, $history->patient_id);
    }
}
