<?php

namespace Tests;

use App\Models\AiAnalysis;
use App\Models\CareEpisode;
use App\Models\Patient;
use App\Models\Symptom;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;

abstract class TestCase extends BaseTestCase
{
    protected function makeDoctor(string $email = 'orvos@teszt.hu'): User
    {
        return User::create([
            'name' => 'Dr. Teszt Orvos',
            'email' => $email,
            'password_hash' => Hash::make('titkos123'),
            'role' => 'doctor',
        ]);
    }

    protected function makeAssistant(User $doctor, string $email = 'asszisztens@teszt.hu'): User
    {
        return User::create([
            'name' => 'Teszt Asszisztens',
            'email' => $email,
            'password_hash' => Hash::make('titkos123'),
            'role' => 'assistant',
            'doctor_id' => $doctor->id,
        ]);
    }

    protected function makePatient(array $overrides = []): Patient
    {
        static $seq = 0;
        $seq++;

        return Patient::create(array_merge([
            'taj_number' => 100000000 + $seq,
            'name' => 'Teszt Páciens ' . $seq,
            'birth_date' => '1980.01.01',
            'birth_place' => 'Pécs',
            'gender' => 'F',
            'phone' => '+36301234567',
            'email' => "paciens{$seq}@teszt.hu",
            'address' => '4032 Debrecen, Teszt utca 1.',
            'status' => Patient::STATUS_NO_INFORMATION,
            'is_urgent' => false,
        ], $overrides));
    }

    protected function makeEpisode(Patient $patient, User $opener, string $status = CareEpisode::STATUS_WAITING_FOR_DIAGNOSIS): CareEpisode
    {
        return $patient->episodes()->create([
            'opened_by' => $opener->id,
            'title' => 'Teszt ellátási eset',
            'status' => $status,
            'opened_at' => now(),
        ]);
    }

    protected function makeSymptom(string $name = 'Fejfájás'): Symptom
    {
        return Symptom::create([
            'name' => $name,
            'description' => 'Teszt tünet',
        ]);
    }

    protected function makePendingAnalysis(Patient $patient, CareEpisode $episode, User $doctor): AiAnalysis
    {
        return $patient->aiAnalyses()->create([
            'episode_id' => $episode->id,
            'doctor_id' => $doctor->id,
            'status' => AiAnalysis::STATUS_PENDING,
            'suggestions' => [
                ['name' => 'Akut bronchitis', 'confidence' => 72, 'explanation' => 'Köhögés és láz.', 'lab_consistency' => 'CRP emelkedett.'],
                ['name' => 'Szezonális influenza', 'confidence' => 54, 'explanation' => 'Izomfájdalom.', 'lab_consistency' => 'Nem mond ellent.'],
            ],
            'input_snapshot' => ['symptoms_used' => ['Köhögés'], 'abnormal_results_used' => ['CRP']],
            'model' => 'teszt-modell',
        ]);
    }
}
