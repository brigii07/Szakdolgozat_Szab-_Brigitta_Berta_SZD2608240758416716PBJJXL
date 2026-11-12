<?php

namespace Database\Seeders;

use App\Models\Bloodtest;
use App\Models\CareEpisode;
use App\Models\Document;
use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Models\Symptom;
use App\Models\User;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Database\Seeder;

class PatientSeeder extends Seeder
{
    private Generator $faker;

    private const BLOOD_PARAMS = [
        ['name' => 'WBC', 'unit' => '10^9/L', 'min' => 4.0, 'max' => 10.0],
        ['name' => 'RBC', 'unit' => '10^12/L', 'min' => 4.5, 'max' => 5.9],
        ['name' => 'Hemoglobin', 'unit' => 'g/L', 'min' => 120, 'max' => 160],
        ['name' => 'CRP', 'unit' => 'mg/L', 'min' => 0, 'max' => 5],
        ['name' => 'Neutrofil %', 'unit' => '%', 'min' => 40, 'max' => 70],
    ];

    private const DIAGNOSIS_POOL = [
        'Felső légúti bakteriális fertőzés',
        'Akut bronchitis',
        'Szezonális influenza',
        'Vírusos gastroenteritis',
        'Migrén',
        'Vashiányos vérszegénység',
    ];

    private const EPISODE_TITLES = [
        'Légúti panaszok',
        'Lázas állapot kivizsgálása',
        'Fejfájás kivizsgálása',
        'Emésztőrendszeri panaszok',
        'Fáradékonyság kivizsgálása',
        'Kontrollvizsgálat',
    ];

    public function run(): void
    {
        $this->faker = Factory::create('hu_HU');
        $doctors = User::where('role', 'doctor')->get();
        $symptoms = Symptom::all();

        if ($doctors->isEmpty() || $symptoms->isEmpty()) {
            $this->command->warn('A PatientSeeder előtt futtatni kell a UserSeeder és a SymptomSeeder osztályokat.');
            return;
        }

        $usedTaj = [];

        for ($i = 0; $i < 18; $i++) {
            do {
                $taj = $this->faker->numberBetween($min = 100000000, $max = 999999999);
            } while (in_array($taj, $usedTaj, true));
            $usedTaj[] = $taj;

            $doctor = $doctors->random();
            $patient = Patient::create([
                'taj_number' => $taj,
                'name' => $this->faker->name(),
                'birth_date' => $this->faker->date('Y.m.d', '-1 year'),
                'birth_place' => $this->faker->city(),
                'gender' => $this->faker->randomElement(['M', 'F']),
                'phone' => $this->faker->phoneNumber(),
                'email' => $this->faker->unique()->safeEmail(),
                'address' => $this->faker->address(),
                'status' => Patient::STATUS_NO_INFORMATION,
                'is_urgent' => $this->faker->boolean(20),
            ]);

            if ($this->faker->boolean(35)) {
                $this->makeEpisode(
                    $patient, $doctor, $symptoms,
                    CareEpisode::STATUS_DIAGNOSED,
                    $this->faker->numberBetween(90, 200)
                );
            }

            $currentStatus = $this->faker->randomElement([
                CareEpisode::STATUS_NO_INFORMATION,
                CareEpisode::STATUS_WAITING_FOR_DIAGNOSIS,
                CareEpisode::STATUS_WAITING_FOR_DIAGNOSIS,
                CareEpisode::STATUS_DIAGNOSED,
            ]);

            $this->makeEpisode(
                $patient, $doctor, $symptoms,
                $currentStatus,
                $this->faker->numberBetween(0, 20)
            );

            if ($this->faker->boolean(40)) {
                $patient->medicalHistories()->create([
                    'recorded_by' => $doctor->id,
                    'type' => $this->faker->randomElement(MedicalHistory::TYPES),
                    'title' => $this->faker->randomElement([
                        'Tüdőgyulladás gyermekkorban',
                        'Magas vérnyomás, kezelt',
                        'Korábbi vashiány',
                        'Panaszok fokozódtak az elmúlt napokban',
                    ]),
                    'description' => $this->faker->sentence(10),
                    'occurred_at' => $this->faker->dateTimeBetween('-6 years', '-1 month'),
                ]);
            }

            if ($this->faker->boolean(50)) {
                $patient->notes()->create([
                    'doctor_id' => $doctor->id,
                    'author_id' => $doctor->id,
                    'note' => $this->faker->sentence(15),
                ]);
            }
        }
    }

    private function makeEpisode(Patient $patient, User $doctor, $symptoms, string $status, int $daysAgo): CareEpisode
    {
        $openedAt = now()->subDays($daysAgo)->subHours($this->faker->numberBetween(0, 12));
        $isClosed = !in_array($status, CareEpisode::OPEN_STATUSES, true);

        $episode = $patient->episodes()->create([
            'opened_by' => $doctor->id,
            'title' => $this->faker->randomElement(self::EPISODE_TITLES),
            'status' => $status,
            'status_changed_at' => $openedAt,
            'opened_at' => $openedAt,
            'closed_at' => $isClosed ? $openedAt->copy()->addDays($this->faker->numberBetween(1, 10)) : null,
        ]);

        if ($status !== CareEpisode::STATUS_NO_INFORMATION) {
            foreach ($symptoms->random($this->faker->numberBetween(1, 3)) as $symptom) {
                $detail = null;
                if ($symptom->detail_type === 'number') {
                    $detail = (string)$this->faker->randomFloat(1, 37.5, 39.8);
                } elseif ($symptom->detail_type === 'text') {
                    $detail = $this->faker->randomElement(['Penicillin', 'Jobb alkaron', 'Tejtermék', 'Esti órákban']);
                }
                $patient->symptoms()->attach($symptom->id, [
                    'episode_id' => $episode->id,
                    'severity' => $this->faker->randomElement(['enyhe', 'közepes', 'súlyos']),
                    'detail' => $detail,
                    'created_at' => $openedAt,
                ]);
            }
        }

        if ($this->faker->boolean(70)) {
            $bloodtest = $patient->bloodtests()->create([
                'episode_id' => $episode->id,
                'doctor_id' => $doctor->id,
                'source' => $this->faker->boolean(25) ? Bloodtest::SOURCE_AI_IMPORT : Bloodtest::SOURCE_MANUAL,
                'created_at' => $openedAt->copy()->addHours(2),
            ]);

            foreach (self::BLOOD_PARAMS as $param) {
                $bloodtest->results()->create([
                    'parameter_name' => $param['name'],
                    'value' => (string)$this->faker->randomFloat(1, $param['min'] * 0.7, $param['max'] * 1.3),
                    'unit' => $param['unit'],
                    'reference_min' => (string)$param['min'],
                    'reference_max' => (string)$param['max'],
                ]);
            }
        }

        $measurementCount = $this->faker->numberBetween(2, 3);
        $height = $this->faker->numberBetween(155, 195);

        for ($m = 0; $m < $measurementCount; $m++) {
            $patient->vitalSigns()->create([
                'episode_id' => $episode->id,
                'recorded_by' => $doctor->id,
                'systolic' => $this->faker->numberBetween(105, 165),
                'diastolic' => $this->faker->numberBetween(62, 98),
                'pulse' => $this->faker->numberBetween(58, 110),
                'temperature' => $this->faker->randomFloat(1, 36.1, 39.2),
                'spo2' => $this->faker->numberBetween(92, 100),
                'weight' => $this->faker->randomFloat(1, 52, 105),
                'height' => $height,
                'measured_at' => $openedAt->copy()->addHours($m * 8),
            ]);
        }

        if ($status === CareEpisode::STATUS_DIAGNOSED) {
            $patient->diagnoses()->create([
                'episode_id' => $episode->id,
                'doctor_id' => $doctor->id,
                'diagnosis' => $this->faker->randomElement(self::DIAGNOSIS_POOL),
                'description' => $this->faker->sentence(12),
            ]);

            if ($this->faker->boolean(50)) {
                $type = $this->faker->randomElement([
                    Document::TYPE_CERTIFICATE,
                    Document::TYPE_REFERRAL,
                    Document::TYPE_REPORT,
                    Document::TYPE_SICK_LEAVE,
                ]);

                $isSickLeave = $type === Document::TYPE_SICK_LEAVE;
                $validFrom = $openedAt->copy()->startOfDay();

                $patient->documents()->create([
                    'episode_id' => $episode->id,
                    'doctor_id' => $doctor->id,
                    'type' => $type,
                    'file_path' => 'documents/placeholder.pdf',
                    'content' => $this->faker->sentence(18),
                    'ai_drafted' => $this->faker->boolean(30),
                    'valid_from' => $isSickLeave ? $validFrom : null,
                    'valid_to' => $isSickLeave ? $validFrom->copy()->addDays($this->faker->numberBetween(3, 21)) : null,
                    'incapacity_reason' => $isSickLeave ? $this->faker->randomElement(Document::INCAPACITY_REASONS) : null,
                ]);
            }
        }
        $patient->forceFill(['status_changed_at' => $episode->status_changed_at])->saveQuietly();
        return $episode;
    }
}
