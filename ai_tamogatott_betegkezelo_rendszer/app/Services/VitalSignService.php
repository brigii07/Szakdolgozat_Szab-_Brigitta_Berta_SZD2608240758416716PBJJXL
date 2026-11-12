<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\VitalSign;
use Illuminate\Contracts\Auth\Authenticatable;

class VitalSignService
{
    public function addVitalSign(Patient $patient, array $data, Authenticatable $user): VitalSign
    {
        if (empty($data['height']) && !empty($data['weight'])) {
            $data['height'] = $patient->vitalSigns()->whereNotNull('height')->value('height');
        }

        $episode = $patient->requireOpenEpisode();
        $vital = $patient->vitalSigns()->create([
            ...$data,
            'episode_id' => $episode->id,
            'measured_at' => $data['measured_at'] ?? now(),
            'recorded_by' => $user->id,
        ]);

        ActivityLogger::log('Vitális paraméterek rögzítve' . ($vital->hasAbnormal() ? ' (eltéréssel)' : ''), $patient->id);
        return $vital;
    }

    public function deleteVitalSign(VitalSign $vital): void
    {
        $vital->episode?->requireNotClosed('Mérés törlése');
        $vital->delete();
        ActivityLogger::log('Vitális mérés törölve', $vital->patient_id);
    }
}
