<?php

namespace App\Services;

use App\Models\Patient;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Auth\Authenticatable;

class PatientSummaryService
{
    public function makePdf(Patient $patient, Authenticatable $user)
    {
        $patient->load(['symptoms', 'diagnoses.doctor', 'medicalHistories', 'documents', 'episodes.diagnoses']);
        $episodeLabels = $patient->episodes->mapWithKeys(fn($episode) => [
            $episode->id => $episode->displayTitle() . ' (' . $episode->periodLabel() . ')',
        ]);

        return Pdf::loadView('patients.summary-pdf', [
            'patient' => $patient,
            'generatedBy' => $user,
            'episodeLabels' => $episodeLabels,
            'latestBloodtest' => $patient->bloodtests()->with('results')->latest('created_at')->first(),
            'vitals' => $patient->vitalSigns()->take(5)->get(),
            'notes' => $patient->notes()->with(['doctor', 'author'])->latest('created_at')->take(5)->get(),
        ])->setPaper('a4');
    }
}
