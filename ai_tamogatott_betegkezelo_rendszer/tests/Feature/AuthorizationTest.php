<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;
    private User $assistant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->doctor = $this->makeDoctor();
        $this->assistant = $this->makeAssistant($this->doctor);
    }

    public function test_vendeg_nem_erheti_el_a_pacienslistat(): void
    {
        $this->get(route('patients.index'))->assertRedirect(route('login'));
    }

    public function test_asszisztens_nem_rogzithat_diagnozist(): void
    {
        $patient = $this->makePatient();
        $this->makeEpisode($patient, $this->doctor);

        $this->actingAs($this->assistant)
            ->post(route('diagnoses.store', $patient), [
                'diagnosis' => 'Influenza',
                'description' => 'Teszt leírás.',
            ])
            ->assertForbidden();
        $this->assertDatabaseCount('diagnoses', 0);
    }

    public function test_assziszens_nem_hasznalhatja_az_ai_asszisztenst(): void
    {
        $patient = $this->makePatient();
        $this->actingAs($this->assistant)
            ->get(route('ai.analyze', $patient))
            ->assertForbidden();
    }

    public function test_asszisztens_nem_allithat_ki_dokumentumot(): void
    {
        $this->actingAs($this->assistant)
            ->get(route('documents.create'))
            ->assertForbidden();
    }

    public function test_asszisztens_nem_torolhet_pacienst(): void
    {
        $patient = $this->makePatient();
        $this->actingAs($this->assistant)
            ->delete(route('patients.destroy', $patient))
            ->assertForbidden();

        $this->assertDatabaseHas('patients', ['id' => $patient->id]);
    }

    public function test_asszisztens_nem_tolthet_le_osszefoglalot(): void
    {
        $patient = $this->makePatient();
        $this->actingAs($this->assistant)
            ->get(route('patients.summary', $patient))
            ->assertForbidden();
    }

    public function test_orvos_eleri_a_korlatozott_vegpontokat(): void
    {
        $patient = $this->makePatient();
        $this->makeEpisode($patient, $this->doctor);
        $this->actingAs($this->doctor)->get(route('ai.analyze', $patient))->assertOk();
        $this->actingAs($this->doctor)->get(route('documents.create'))->assertOk();
    }

    public function test_asszisztens_rogzithet_tunetet(): void
    {
        $patient = $this->makePatient();
        $this->makeEpisode($patient, $this->doctor);
        $symptom = $this->makeSymptom();

        $this->actingAs($this->assistant)
            ->post(route('symptoms.store', $patient), [
                'symptom_ids' => [$symptom->id],
                'severity' => 'közepes',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('symptom_patient', 1);
    }
}
