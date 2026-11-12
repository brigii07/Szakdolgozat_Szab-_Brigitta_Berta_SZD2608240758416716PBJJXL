<?php

namespace Tests\Feature;

use App\Models\AiAnalysis;
use App\Models\CareEpisode;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiAnalysisTest extends TestCase
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
        $this->episode = $this->makeEpisode($this->patient, $this->doctor, CareEpisode::STATUS_WAITING_AI_APPROVAL);
    }

    public function test_elfogadaskor_a_tarolt_javaslat_adatai_kerulnek_be(): void
    {
        $analysis = $this->makePendingAnalysis($this->patient, $this->episode, $this->doctor);
        $this->actingAs($this->doctor)
            ->post(route('ai.accept', [$this->patient, $analysis]), [
                'source' => 'ai',
                'selected_rank' => 2,
                'summary' => 'Az orvos által pontosított összefoglaló.',
                'recommendation' => 'Pihenés, folyadékpótlás.',
            ])
            ->assertRedirect();

        $analysis->refresh();
        $this->assertSame(AiAnalysis::STATUS_ACCEPTED, $analysis->status);
        $this->assertSame('Szezonális influenza', $analysis->selected_diagnosis);
        $this->assertSame(2, $analysis->selected_rank);
        $this->assertSame(54, $analysis->confidence);
        $this->assertDatabaseHas('diagnoses', ['diagnosis' => 'Szezonális influenza']);
    }

    public function test_a_kliens_altal_kuldott_diagnozisnev_nem_kerul_be(): void
    {
        $analysis = $this->makePendingAnalysis($this->patient, $this->episode, $this->doctor);

        $this->actingAs($this->doctor)
            ->post(route('ai.accept', [$this->patient, $analysis]), [
                'source' => 'ai',
                'selected_rank' => 1,
                'diagnosis_name' => 'Soha nem javasolt betegség',
                'confidence' => 99,
                'summary' => 'Összefoglaló.',
                'recommendation' => 'Teendő.',
            ])
            ->assertRedirect();

        $analysis->refresh();
        $this->assertSame('Akut bronchitis', $analysis->selected_diagnosis);
        $this->assertSame(72, $analysis->confidence);
        $this->assertDatabaseMissing('diagnoses', ['diagnosis' => 'Soha nem javasolt betegség']);
    }

    public function test_a_tartomanyon_kivuli_rang_elutasitasra_kerul(): void
    {
        $analysis = $this->makePendingAnalysis($this->patient, $this->episode, $this->doctor);
        $this->actingAs($this->doctor)
            ->post(route('ai.accept', [$this->patient, $analysis]), [
                'source' => 'ai',
                'selected_rank' => 9,
                'summary' => 'Összefoglaló.',
                'recommendation' => 'Teendő.',
            ])
            ->assertSessionHasErrors('selected_rank');

        $this->assertSame(AiAnalysis::STATUS_PENDING, $analysis->fresh()->status);
        $this->assertDatabaseCount('diagnoses', 0);
    }

    public function test_kezi_megadasnal_nincs_rang_es_megbizhatosag(): void
    {
        $analysis = $this->makePendingAnalysis($this->patient, $this->episode, $this->doctor);
        $this->actingAs($this->doctor)
            ->post(route('ai.accept', [$this->patient, $analysis]), [
                'source' => 'manual',
                'custom_diagnosis_name' => 'Orvos saját diagnózisa',
                'summary' => 'Saját összefoglaló.',
                'recommendation' => 'Teendő.',
            ])
            ->assertRedirect();

        $analysis->refresh();
        $this->assertSame('Orvos saját diagnózisa', $analysis->selected_diagnosis);
        $this->assertNull($analysis->selected_rank);
        $this->assertNull($analysis->confidence);
    }

    public function test_kezi_megadasnal_a_nev_kotelezo(): void
    {
        $analysis = $this->makePendingAnalysis($this->patient, $this->episode, $this->doctor);
        $this->actingAs($this->doctor)
            ->post(route('ai.accept', [$this->patient, $analysis]), [
                'source' => 'manual',
                'summary' => 'Összefoglaló.',
                'recommendation' => 'Teendő.',
            ])
            ->assertSessionHasErrors('custom_diagnosis_name');
    }

    public function test_lezart_eset_javaslata_nem_fogadhato_el(): void
    {
        $analysis = $this->makePendingAnalysis($this->patient, $this->episode, $this->doctor);
        $this->episode->update(['status' => CareEpisode::STATUS_CLOSED]);

        $this->actingAs($this->doctor)
            ->post(route('ai.accept', [$this->patient, $analysis]), [
                'source' => 'ai',
                'selected_rank' => 1,
                'summary' => 'Összefoglaló.',
                'recommendation' => 'Teendő.',
            ])
            ->assertStatus(409);

        $this->assertSame(AiAnalysis::STATUS_PENDING, $analysis->fresh()->status);
        $this->assertSame(CareEpisode::STATUS_CLOSED, $this->episode->fresh()->status);
        $this->assertDatabaseCount('diagnoses', 0);
    }

    public function test_eset_lezarasa_ervenyteleniti_a_fuggo_javaslatot(): void
    {
        $analysis = $this->makePendingAnalysis($this->patient, $this->episode, $this->doctor);
        $this->actingAs($this->doctor)
            ->patch(route('episodes.close', [$this->patient, $this->episode]), [
                'closing_note' => 'A beteg nem jelent meg.',
            ])
            ->assertRedirect();
        $this->assertSame(AiAnalysis::STATUS_SUPERSEDED, $analysis->fresh()->status);
    }

    public function test_elutasitas_utan_az_eset_diagnozisra_var(): void
    {
        $analysis = $this->makePendingAnalysis($this->patient, $this->episode, $this->doctor);
        $this->actingAs($this->doctor)
            ->post(route('ai.reject', [$this->patient, $analysis]), [
                'rejection_reason' => 'A laborértékek ellentmondanak a javaslatnak.',
            ])
            ->assertRedirect();

        $this->assertSame(AiAnalysis::STATUS_REJECTED, $analysis->fresh()->status);
        $this->assertSame(CareEpisode::STATUS_WAITING_FOR_DIAGNOSIS, $this->episode->fresh()->status);
    }

    public function test_mar_eldontott_elemzesrol_nem_lehet_ujra_donteni(): void
    {
        $analysis = $this->makePendingAnalysis($this->patient, $this->episode, $this->doctor);
        $analysis->update(['status' => AiAnalysis::STATUS_REJECTED]);
        $this->actingAs($this->doctor)
            ->post(route('ai.accept', [$this->patient, $analysis]), [
                'source' => 'ai',
                'selected_rank' => 1,
                'summary' => 'Összefoglaló.',
                'recommendation' => 'Teendő.',
            ])
            ->assertRedirect(route('ai.analyze', $this->patient));

        $this->assertSame(AiAnalysis::STATUS_REJECTED, $analysis->fresh()->status);
        $this->assertDatabaseCount('diagnoses', 0);
    }

    public function test_nyitott_eset_nelkul_az_elemzes_uzenettel_all_meg(): void
    {
        $this->episode->update(['status' => CareEpisode::STATUS_CLOSED]);
        $this->actingAs($this->doctor)
            ->post(route('ai.generate', $this->patient))
            ->assertRedirect(route('ai.analyze', $this->patient))
            ->assertSessionHasErrors('ai');
    }
}
