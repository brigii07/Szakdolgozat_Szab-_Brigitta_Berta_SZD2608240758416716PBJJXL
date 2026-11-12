<?php

namespace App\Services;

use App\Models\CareEpisode;
use App\Models\Diagnosis;
use App\Models\Patient;
use Illuminate\Contracts\Auth\Authenticatable;

class DiagnosisService
{

    public function addDiagnosis(Patient $patient, array $data, Authenticatable $doctor): Diagnosis
    {
        $episode = $patient->requireOpenEpisode();
        $data['doctor_id'] = $doctor->id;
        $data['episode_id'] = $episode->id;
        $diagnosis = $patient->diagnoses()->create($data);
        $episode->update(['status' => CareEpisode::STATUS_DIAGNOSED]);
        ActivityLogger::log('Diagnózis rögzítve: ' . $diagnosis->diagnosis, $patient->id);
        return $diagnosis;
    }

    public function updateDiagnosis(Patient $patient, Diagnosis $diagnosis, array $data): Diagnosis
    {
        $diagnosis->episode?->requireNotClosed('Diagnózis módosítása');
        $diagnosis->update($data);
        ActivityLogger::log('Diagnózis szerkesztve: ' . $diagnosis->diagnosis, $patient->id);
        return $diagnosis;
    }
}
