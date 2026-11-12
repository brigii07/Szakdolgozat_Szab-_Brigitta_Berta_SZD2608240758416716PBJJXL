<?php

namespace App\Services;

use App\Models\Bloodtest;
use App\Models\LogEntry;
use App\Models\Patient;
use App\Models\Symptom;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DashboardService
{
    private const LIST_LIMIT = 8;
    private const LAB_LOOKBACK_DAYS = 10;

    public function overview(User $user): array
    {
        $data = [
            'urgentPatients' => Patient::where('is_urgent', true)
                ->where('status', '!=', Patient::STATUS_DIAGNOSED)
                ->orderBy('created_at')
                ->take(12)
                ->get(['id', 'name', 'status', 'created_at']),
            'waitingForDiagnosis' => $this->queue(Patient::STATUS_WAITING_FOR_DIAGNOSIS),
            'waitingAiApproval' => $this->queue(Patient::STATUS_WAITING_AI_APPROVAL),
        ];

        if (Gate::allows('manage-diagnosis')) {
            $data['abnormalLabs'] = $this->abnormalLabResults();
            $data['recentActivity'] = $this->recentTeamActivity($user);
        }
        return $data;
    }

    private function queue(string $status): array
    {
        return [
            'count' => Patient::where('status', $status)->count(),
            'patients' => Patient::where('status', $status)
                ->select(['id', 'name', 'taj_number', 'is_urgent', 'status_changed_at'])
                ->withCount(['symptoms' => fn($q) => $q->whereColumn('symptom_patient.episode_id', '=', DB::raw('(select ce.id from care_episodes ce where ce.patient_id = patients.id order by ce.opened_at desc, ce.id desc limit 1)')
                )])
                ->orderByDesc('is_urgent')
                ->orderBy('status_changed_at')
                ->take(self::LIST_LIMIT)
                ->get(),
        ];
    }

    private function abnormalLabResults(): Collection
    {
        return Bloodtest::with(['patient:id,name', 'results'])
            ->where('created_at', '>=', now()->subDays(self::LAB_LOOKBACK_DAYS))
            ->latest('created_at')
            ->get()
            ->map(fn(Bloodtest $test) => [
                'patient' => $test->patient,
                'created_at' => $test->created_at,
                'results' => $test->results->filter->isOutOfRange()->values(),
            ])
            ->filter(fn(array $row) => $row['patient'] && $row['results']->isNotEmpty())
            ->take(self::LIST_LIMIT)
            ->values();
    }

    private function recentTeamActivity(User $doctor): Collection
    {
        $teamIds = $doctor->assistants()->pluck('id')->push($doctor->id);
        return LogEntry::with(['user:id,name,role', 'patient:id,name'])
            ->whereIn('user_id', $teamIds)
            ->latest('created_at')
            ->take(self::LIST_LIMIT)
            ->get();
    }

}
