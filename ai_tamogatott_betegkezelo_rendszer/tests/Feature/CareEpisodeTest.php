<?php

namespace Tests\Feature;

use App\Models\CareEpisode;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CareEpisodeTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->doctor = $this->makeDoctor();
        $this->patient = $this->makePatient();
    }

    public function test_uj_ellatasi_eset_inditasa(): void
    {
        $this->actingAs($this->doctor)
            ->post(route('episodes.store', $this->patient), ['title' => 'Légúti panaszok'])
            ->assertRedirect();

        $this->assertDatabaseHas('care_episodes', [
            'patient_id' => $this->patient->id,
            'title' => 'Légúti panaszok',
            'status' => CareEpisode::STATUS_NO_INFORMATION,
        ]);
    }

    public function test_uj_eset_lezarja_a_korabbi_nyitottat(): void
    {
        $regi = $this->makeEpisode($this->patient, $this->doctor);

        $this->actingAs($this->doctor)
            ->post(route('episodes.store', $this->patient), ['title' => 'Új panasz']);

        $this->assertSame(CareEpisode::STATUS_CLOSED, $regi->fresh()->status);
        $this->assertSame(1, $this->patient->episodes()
            ->whereIn('status', CareEpisode::OPEN_STATUSES)->count());
    }

    public function test_a_paciens_statusza_koveti_az_eset_allapotat(): void
    {
        $episode = $this->makeEpisode($this->patient, $this->doctor);
        $episode->update(['status' => CareEpisode::STATUS_DIAGNOSED]);

        $this->assertSame(Patient::STATUS_DIAGNOSED, $this->patient->fresh()->status);
    }

    public function test_lezart_esethez_nem_rogzitheto_tunet(): void
    {
        $this->makeEpisode($this->patient, $this->doctor, CareEpisode::STATUS_CLOSED);
        $symptom = $this->makeSymptom();

        $this->actingAs($this->doctor)
            ->post(route('symptoms.store', $this->patient), [
                'symptom_ids' => [$symptom->id],
                'severity' => 'enyhe',
            ])
            ->assertStatus(409);

        $this->assertDatabaseCount('symptom_patient', 0);
    }

    public function test_lezart_esethez_nem_rogzitheto_meres(): void
    {
        $this->makeEpisode($this->patient, $this->doctor, CareEpisode::STATUS_CLOSED);
        $this->actingAs($this->doctor)
            ->post(route('vitals.store', $this->patient), ['pulse' => 72])
            ->assertStatus(409);

        $this->assertDatabaseCount('vital_signs', 0);
    }

    public function test_veglegesen_lezart_eset_diagnozisa_nem_modosithato(): void
    {
        $episode = $this->makeEpisode($this->patient, $this->doctor);
        $diagnosis = $this->patient->diagnoses()->create([
            'episode_id' => $episode->id,
            'doctor_id' => $this->doctor->id,
            'diagnosis' => 'Eredeti diagnózis',
            'description' => 'Eredeti leírás.',
        ]);
        $episode->update(['status' => CareEpisode::STATUS_CLOSED]);

        $this->actingAs($this->doctor)
            ->patch(route('diagnoses.update', [$this->patient, $diagnosis]), [
                'diagnosis' => 'Átírt diagnózis',
                'description' => 'Átírt leírás.',
            ])
            ->assertStatus(409);

        $this->assertSame('Eredeti diagnózis', $diagnosis->fresh()->diagnosis);
    }

    public function test_diagnosztizalt_eset_diagnozisa_meg_javithato(): void
    {
        $episode = $this->makeEpisode($this->patient, $this->doctor);
        $diagnosis = $this->patient->diagnoses()->create([
            'episode_id' => $episode->id,
            'doctor_id' => $this->doctor->id,
            'diagnosis' => 'Elgépelt diagnózis',
            'description' => 'Leírás.',
        ]);
        $episode->update(['status' => CareEpisode::STATUS_DIAGNOSED]);

        $this->actingAs($this->doctor)
            ->patch(route('diagnoses.update', [$this->patient, $diagnosis]), [
                'diagnosis' => 'Javított diagnózis',
                'description' => 'Leírás.',
            ])
            ->assertRedirect();

        $this->assertSame('Javított diagnózis', $diagnosis->fresh()->diagnosis);
    }

    public function test_veglegesen_lezart_eset_merese_nem_torolheto(): void
    {
        $episode = $this->makeEpisode($this->patient, $this->doctor);
        $vital = $this->patient->vitalSigns()->create([
            'episode_id' => $episode->id,
            'recorded_by' => $this->doctor->id,
            'pulse' => 70,
            'measured_at' => now(),
        ]);
        $episode->update(['status' => CareEpisode::STATUS_CLOSED]);

        $this->actingAs($this->doctor)
            ->delete(route('vitals.destroy', [$this->patient, $vital]))
            ->assertStatus(409);

        $this->assertDatabaseHas('vital_signs', ['id' => $vital->id]);
    }

    public function test_nyitott_eset_nelkul_nem_rogzitheto_adat(): void
    {
        $this->actingAs($this->doctor)
            ->post(route('vitals.store', $this->patient), ['pulse' => 80])
            ->assertStatus(409);
    }
}
