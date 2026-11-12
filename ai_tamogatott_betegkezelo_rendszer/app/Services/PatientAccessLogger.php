<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\PatientAccessLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PatientAccessLogger
{
    private const VIEW_DEDUP_MINUTES = 5;

    public function record(Patient $patient, string $action): void
    {
        $userId = Auth::id();
        if (!$userId) {
            return;
        }

        if ($action === PatientAccessLog::ACTION_VIEW) {
            $alreadyLogged = PatientAccessLog::where('patient_id', $patient->id)
                ->where('user_id', $userId)
                ->where('action', $action)
                ->where('created_at', '>=', now()->subMinutes(self::VIEW_DEDUP_MINUTES))
                ->exists();
            if ($alreadyLogged) {
                return;
            }
        }

        PatientAccessLog::create([
            'patient_id' => $patient->id,
            'user_id' => $userId,
            'action' => $action,
            'ip_address' => request()->ip(),
            'user_agent' => Str::limit((string)request()->userAgent(), 250, ''),
        ]);
    }
}
