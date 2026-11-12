<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->doctor = $this->makeDoctor();
    }

    private function urlap(array $overrides = []): array
    {
        return array_merge([
            'taj_number' => '123456789',
            'name' => 'Kovács Anna',
            'birth_date' => '1985.03.12',
            'birth_place' => 'Szeged',
            'gender' => 'F',
            'phone' => '+36201234567',
            'email' => 'kovacs.anna@teszt.hu',
            'address' => '6720 Szeged, Fő tér 1.',
        ], $overrides);
    }


    public function test_paciens_felvetele(): void
    {
        $this->actingAs($this->doctor)
            ->post(route('patients.store'), $this->urlap())
            ->assertRedirect();

        $this->assertDatabaseHas('patients', [
            'taj_number' => '123456789',
            'name' => 'Kovács Anna',
            'status' => Patient::STATUS_NO_INFORMATION,
        ]);
    }

    public function test_duplikalt_taj_szam_elutasitasra_kerul(): void
    {
        $this->makePatient(['taj_number' => '123456789', 'email' => 'elso@teszt.hu']);
        $this->actingAs($this->doctor)
            ->post(route('patients.store'), $this->urlap())
            ->assertSessionHasErrors('taj_number');

        $this->assertSame(1, Patient::count());
    }

    public function test_hibas_hosszusagu_taj_szam_elutasitasra_kerul(): void
    {
        $this->actingAs($this->doctor)
            ->post(route('patients.store'), $this->urlap(['taj_number' => '12345']))
            ->assertSessionHasErrors('taj_number');

        $this->assertSame(0, Patient::count());
    }

    public function test_hibas_datumformatum_elutasitasra_kerul(): void
    {
        $this->actingAs($this->doctor)
            ->post(route('patients.store'), $this->urlap(['birth_date' => '1985-03-12']))
            ->assertSessionHasErrors('birth_date');
    }

    public function test_hianyzo_kotelezo_mezok_elutasitasra_kerulnek(): void
    {
        $this->actingAs($this->doctor)
            ->post(route('patients.store'), [])
            ->assertSessionHasErrors(['taj_number', 'name', 'birth_date', 'email']);

        $this->assertSame(0, Patient::count());
    }

    public function test_az_adatlap_megnyitasa_naplozasra_kerul(): void
    {
        $patient = $this->makePatient();

        $this->actingAs($this->doctor)
            ->get(route('patients.show', $patient))
            ->assertOk();

        $this->assertDatabaseHas('patient_access_logs', [
            'patient_id' => $patient->id,
            'user_id' => $this->doctor->id,
            'action' => 'VIEW',
        ]);
    }

    public function test_mas_paciens_esete_nem_nyithato_meg(): void
    {
        $patientA = $this->makePatient();
        $patientB = $this->makePatient();
        $idegenEset = $this->makeEpisode($patientB, $this->doctor);

        $this->actingAs($this->doctor)
            ->get(route('patients.show', $patientA) . '?episode=' . $idegenEset->id)
            ->assertNotFound();
    }
}
