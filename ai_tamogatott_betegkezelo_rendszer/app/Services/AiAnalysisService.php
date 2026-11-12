<?php

namespace App\Services;

use App\Exceptions\AiServiceUnavailableException;
use App\Models\AiAnalysis;
use App\Models\CareEpisode;
use App\Models\Patient;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AiAnalysisService
{
    public function __construct(private readonly AiDiagnosisService $aiDiagnosisService)
    {
    }

    public function pendingFor(Patient $patient, ?CareEpisode $episode = null): ?AiAnalysis
    {
        $episode = $episode ?? $patient->openEpisode;
        if (!$episode) {
            return null;
        }

        return $episode->aiAnalyses()
            ->where('status', AiAnalysis::STATUS_PENDING)
            ->with('doctor:id,name')
            ->latest('created_at')
            ->first();
    }

    public function history(Patient $patient): Collection
    {
        return $patient->aiAnalyses()
            ->where('status', '!=', AiAnalysis::STATUS_PENDING)
            ->with(['doctor:id,name', 'decider:id,name'])
            ->latest('created_at')
            ->take(10)
            ->get();
    }

    /**
     * @throws AiServiceUnavailableException
     */
    public function generateAnalysis(Patient $patient, Authenticatable $doctor): ?AiAnalysis
    {
        $episode = $patient->requireOpenEpisode();
        $result = $this->aiDiagnosisService->analyze($episode);
        if ($result['error']) {
            throw new AiServiceUnavailableException('Az AI szolgáltatás hívása sikertelen.');
        }
        if (empty($result['suggestions'])) {
            return null;
        }

        return DB::transaction(function () use ($patient, $episode, $doctor, $result) {
            $episode->aiAnalyses()
                ->where('status', AiAnalysis::STATUS_PENDING)
                ->update(['status' => AiAnalysis::STATUS_SUPERSEDED]);

            $analysis = $patient->aiAnalyses()->create([
                'episode_id' => $episode->id,
                'doctor_id' => $doctor->id,
                'status' => AiAnalysis::STATUS_PENDING,
                'suggestions' => $result['suggestions'],
                'input_snapshot' => [
                    'symptoms_used' => $result['symptoms_used'],
                    'abnormal_results_used' => $result['abnormal_results_used'],
                ],
                'model' => config('services.ai_diagnosis.model'),
            ]);

            $episode->update(['status' => CareEpisode::STATUS_WAITING_AI_APPROVAL]);
            ActivityLogger::log('AI elemzés készült (' . count($result['suggestions']) . ' javaslat)', $patient->id);
            return $analysis;
        });
    }

    public function acceptSuggestion(Patient $patient, AiAnalysis $analysis, array $data, Authenticatable $doctor): AiAnalysis
    {
        return DB::transaction(function () use ($patient, $analysis, $data, $doctor) {
            $analysis = AiAnalysis::whereKey($analysis->getKey())->lockForUpdate()->firstOrFail();
            abort_unless($analysis->isPending(), 409, 'Erről az elemzésről időközben döntés született.');
            $episode = $analysis->episode;
            $episode?->requireOpen('AI-javaslat elfogadása');
            if (($data['source'] ?? 'manual') === 'ai') {
                $rank = (int)$data['selected_rank'];
                $suggestion = $analysis->suggestions[$rank - 1];
                $diagnosisName = $suggestion['name'];
                $modelConfidence = isset($suggestion['confidence']) ? (int)$suggestion['confidence'] : null;
            } else {
                $rank = null;
                $diagnosisName = $data['custom_diagnosis_name'];
                $modelConfidence = null;
            }

            $diagnosis = $patient->diagnoses()->create([
                'episode_id' => $episode?->id,
                'doctor_id' => $doctor->id,
                'diagnosis' => $diagnosisName,
                'description' => $data['summary'],
            ]);

            $analysis->update([
                'status' => AiAnalysis::STATUS_ACCEPTED,
                'diagnosis_id' => $diagnosis->id,
                'selected_diagnosis' => $diagnosisName,
                'selected_rank' => $rank,
                'summary' => $data['summary'],
                'recommendation' => $data['recommendation'],
                'confidence' => $modelConfidence,
                'decided_by' => $doctor->id,
                'decided_at' => now(),
            ]);

            $episode?->update(['status' => CareEpisode::STATUS_DIAGNOSED]);
            ActivityLogger::log('AI javaslat elfogadva, diagnózis rögzítve: ' . $diagnosis->diagnosis, $patient->id);
            return $analysis;
        });
    }

    public function rejectSuggestion(Patient $patient, AiAnalysis $analysis, string $reason, Authenticatable $doctor): AiAnalysis
    {
        return DB::transaction(function () use ($patient, $analysis, $reason, $doctor) {
            $analysis = AiAnalysis::whereKey($analysis->getKey())->lockForUpdate()->firstOrFail();
            abort_unless($analysis->isPending(), 409, 'Erről az elemzésről időközben döntés született.');

            $analysis->update([
                'status' => AiAnalysis::STATUS_REJECTED,
                'rejection_reason' => $reason,
                'decided_by' => $doctor->id,
                'decided_at' => now(),
            ]);

            $episode = $analysis->episode;
            if ($episode && $episode->isOpen()) {
                $episode->update(['status' => CareEpisode::STATUS_WAITING_FOR_DIAGNOSIS]);
            }

            ActivityLogger::log('AI javaslat elutasítva', $patient->id);
            return $analysis;
        });
    }
}
