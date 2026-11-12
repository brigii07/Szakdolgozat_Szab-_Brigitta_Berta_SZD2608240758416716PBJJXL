<?php

namespace App\Services;

use App\Models\Bloodtest;
use App\Models\Patient;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;

class BloodtestService
{

    public function addBloodtestResults(Patient $patient, array $results, Authenticatable $user, string $source = Bloodtest::SOURCE_MANUAL): Bloodtest
    {
        $episode = $patient->requireOpenEpisode();
        return DB::transaction(function () use ($patient, $results, $user, $source, $episode) {
            $verified = $source === Bloodtest::SOURCE_AI_IMPORT;

            $bloodtest = $patient->bloodtests()->create([
                'doctor_id' => $user->role === 'doctor' ? $user->id : $user->doctor_id,
                'episode_id' => $episode->id,
                'source' => $source,
                'verified_by' => $verified ? $user->id : null,
                'verified_at' => $verified ? now() : null,
            ]);

            foreach ($results as $result) {
                $bloodtest->results()->create($result);
            }
            ActivityLogger::log($verified ? 'Vérkép rögzítve (AI-beolvasás, ellenőrizte: ' . $user->name . ')' : 'Vérkép rögzítve', $patient->id);
            return $bloodtest;
        });
    }
}
