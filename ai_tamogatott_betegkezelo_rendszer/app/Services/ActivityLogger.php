<?php

namespace App\Services;

use App\Models\LogEntry;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public static function log(string $action, ?int $patientId = null): void
    {
        LogEntry::create([
            'user_id' => Auth::id(),
            'patient_id' => $patientId,
            'action' => $action,
        ]);
    }
}
