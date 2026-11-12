<?php

namespace App\Services;

use App\Models\AiAnalysis;
use App\Models\CareEpisode;
use App\Models\Patient;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;

class CareEpisodeService
{
    public function openEpisode(Patient $patient, ?string $title, Authenticatable $user): CareEpisode
    {
        return DB::transaction(function () use ($patient, $title, $user) {
            $patient->episodes()
                ->whereIn('status', CareEpisode::OPEN_STATUSES)
                ->get()
                ->each(fn(CareEpisode $open) => $open->update([
                    'status' => CareEpisode::STATUS_CLOSED,
                    'closing_note' => 'Automatikusan lezárva új eset indításakor.',
                ]));

            $episode = $patient->episodes()->create([
                'opened_by' => $user->id,
                'title' => filled($title) ? $title : null,
                'status' => CareEpisode::STATUS_NO_INFORMATION,
                'opened_at' => now(),
            ]);

            ActivityLogger::log('Új ellátási eset indítva' . (filled($title) ? ': ' . $title : ''), $patient->id);
            return $episode;
        });
    }

    public function closeEpisode(CareEpisode $episode, ?string $note = null): CareEpisode
    {
        return DB::transaction(function () use ($episode, $note) {
            $episode->aiAnalyses()->where('status', AiAnalysis::STATUS_PENDING)->update(['status' => AiAnalysis::STATUS_SUPERSEDED]);
            $episode->update([
                'status' => CareEpisode::STATUS_CLOSED,
                'closing_note' => $note,
            ]);
            ActivityLogger::log('Ellátási eset lezárva diagnózis nélkül', $episode->patient_id);
            return $episode;
        });
    }

    public function advanceEpisode(CareEpisode $episode, string $status): void
    {
        if ($episode->status !== $status) {
            $episode->update(['status' => $status]);
        }
    }
}
