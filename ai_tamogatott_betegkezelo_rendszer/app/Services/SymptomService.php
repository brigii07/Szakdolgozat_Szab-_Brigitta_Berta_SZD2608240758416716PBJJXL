<?php

namespace App\Services;

use App\Models\CareEpisode;
use App\Models\Patient;
use App\Models\Symptom;

class SymptomService
{
    public function addSymptoms(Patient $patient, array $data): void
    {
        $episode = $patient->requireOpenEpisode();
        $symptomIds = $data['symptom_ids'] ?? [];
        if (!empty($data['other_symptom'])) {
            $symptom = Symptom::firstOrCreate(
                ['name' => $data['other_symptom']],
                ['description' => 'Felhasználó által rögzített egyéb tünet']
            );
            $symptomIds[] = $symptom->id;
        }

        $details = $data['symptom_details'] ?? [];
        $numericIds = Symptom::whereIn('id', $symptomIds)->where('detail_type', 'number')->pluck('id')->all();

        foreach ($symptomIds as $symptomId) {
            $detail = isset($details[$symptomId]) ? trim($details[$symptomId]) : null;
            if ($detail !== null && in_array((int)$symptomId, $numericIds, true)) {
                $detail = str_replace(',', '.', $detail);
            }

            $patient->symptoms()->attach($symptomId, [
                'severity' => $data['severity'],
                'detail' => filled($detail) ? $detail : null,
                'episode_id' => $episode->id,
                'created_at' => now(),
            ]);
        }
        if ($episode->status === CareEpisode::STATUS_NO_INFORMATION) {
            $episode->update(['status' => CareEpisode::STATUS_WAITING_FOR_DIAGNOSIS]);
        }
        ActivityLogger::log('Tünetek rögzítve', $patient->id);
    }
}
