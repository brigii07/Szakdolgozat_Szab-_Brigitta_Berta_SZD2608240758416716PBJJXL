<?php

namespace Tests\Feature;

use App\Models\Bloodtest;
use App\Models\CareEpisode;
use App\Models\Patient;
use App\Models\User;
use Faker\Core\Blood;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BloodtestImportTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;
    private Patient $patient;
    private CareEpisode $episode;

    protected function setUp(): void
    {
        parent::setUp();
        $this->doctor = $this->makeDoctor();
        $this->patient = $this->makePatient();
        $this->episode = $this->makeEpisode($this->patient, $this->doctor);
    }

    private function sor(array $overrides = []): array
    {
        return array_merge([
            'parameter_name' => 'CRP',
            'value' => '12.4',
            'unit' => 'mg/L',
            'reference_min' => '0',
            'reference_max' => '5',
        ], $overrides);
    }

    public function test_kezi_rogzites_mentheto(): void
    {
        $this->actingAs($this->doctor)
            ->post(route('bloodtests.store', $this->patient), [
                'source' => 'manual',
                'results' => [$this->sor()],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('bloodtests', [
            'patient_id' => $this->patient->id,
            'source' => Bloodtest::SOURCE_MANUAL,
            'verified_by' => null,
        ]);
    }

    public function test_ai_beolvasas_nyugtazas_nelkul_nem_mentheto(): void
    {
        $this->actingAs($this->doctor)
            ->post(route('bloodtests.store', $this->patient), [
                'source' => 'ai_import',
                'results' => [$this->sor()],
            ])
            ->assertSessionHasErrors('verified');

        $this->assertDatabaseCount('bloodtests', 0);
    }

    public function test_nyugtazott_ai_beolvasas_rogziti_az_ellenorzot(): void
    {
        $this->actingAs($this->doctor)
            ->post(route('bloodtests.store', $this->patient), [
                'source' => 'ai_import',
                'verified' => '1',
                'results' => [$this->sor()],
            ])
            ->assertRedirect();

        $bloodtest = Bloodtest::firstOrFail();
        $this->assertSame(Bloodtest::SOURCE_AI_IMPORT, $bloodtest->source);
        $this->assertSame($this->doctor->id, $bloodtest->verified_by);
        $this->assertNotNull($bloodtest->verified_at);
    }

    public function test_a_naplo_az_ellenorzo_nevet_tartalmazza(): void
    {
        $this->actingAs($this->doctor)
            ->post(route('bloodtests.store', $this->patient), [
                'source' => 'ai_import',
                'verified' => '1',
                'results' => [$this->sor()],
            ]);

        $this->assertDatabaseHas('logs', ['action' => 'Vérkép rögzítve (AI-beolvasás, ellenőrizte: ' . $this->doctor->name . ')',]);
    }

    public function test_egyoldali_referenciatartomany_mentheto(): void
    {
        $this->actingAs($this->doctor)
            ->post(route('bloodtests.store', $this->patient), [
                'source' => 'manual',
                'results' => [$this->sor([
                    'parameter_name' => 'eGFR',
                    'value' => '88',
                    'unit' => 'ml/perc',
                    'reference_min' => '60',
                    'reference_max' => '',
                ])],
            ])
            ->assertRedirect();
        $this->assertDatabaseHas('bloodtests_result', [
            'parameter_name' => 'eGFR',
            'reference_max' => null,
        ]);
    }

    public function test_forditott_referenciatartomany_elutasitasra_kerul(): void
    {
        $this->actingAs($this->doctor)
            ->post(route('bloodtests.store', $this->patient), [
                'source' => 'manual',
                'results' => [$this->sor(['reference_min' => '10', 'reference_max' => '2'])],
            ])
            ->assertSessionHasErrors();

        $this->assertDatabaseCount('bloodtests', 0);
    }

    public function test_lezart_esethez_nem_mentheto_verkep(): void
    {
        $this->episode->update(['status' => CareEpisode::STATUS_CLOSED]);

        $this->actingAs($this->doctor)
            ->post(route('bloodtests.store', $this->patient), [
                'source' => 'manual',
                'results' => [$this->sor()],
            ])
            ->assertStatus(409);

        $this->assertDatabaseCount('bloodtests', 0);
    }
}
