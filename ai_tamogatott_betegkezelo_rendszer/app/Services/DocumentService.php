<?php

namespace App\Services;

use App\Models\CareEpisode;
use App\Models\Document;
use App\Models\Patient;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DocumentService
{

    public function selectablePatients()
    {
        return Patient::orderBy('name')->get(['id', 'name', 'birth_date', 'status']);
    }

    public function addDocument(array $data, Authenticatable $doctor): Document
    {
        $patient = Patient::findOrFail($data['patient_id']);
        $episode = $patient->episodes()->first();
        abort_if(Document::requiresDiagnosedPatient($data['type']) && (!$episode || $episode->status !== CareEpisode::STATUS_DIAGNOSED),
            403, 'Ez a dokumentumtípus csak diagnózissal lezárt ellátási esethez állítható ki.'
        );

        return DB::transaction(function () use ($data, $doctor, $patient, $episode) {
            $document = $patient->documents()->create([
                'doctor_id' => $doctor->id,
                'episode_id' => $episode?->id,
                'type' => $data['type'],
                'file_path' => '',
                'content' => $data['note'] ?? null,
                'ai_drafted' => (bool)($data['ai_drafted'] ?? false),
                'valid_from' => $data['valid_from'] ?? null,
                'valid_to' => $data['valid_to'] ?? null,
                'incapacity_reason' => $data['incapacity_reason'] ?? null,
            ]);

            $latestDiagnosis = $episode?->diagnoses()->latest('created_at')->first();
            $pdf = Pdf::loadView('documents.pdf', [
                'document' => $document,
                'patient' => $patient,
                'doctor' => $doctor,
                'diagnosis' => $latestDiagnosis,
                'note' => $data['note'] ?? null,
            ])->setPaper('a4');

            $relativePath = "documents/{$document->id}.pdf";
            Storage::disk('local')->put($relativePath, $pdf->output());
            $document->update(['file_path' => $relativePath]);
            ActivityLogger::log('Dokumentum kiállítva: ' . $data['type'] . (!empty($data['ai_drafted']) ? ' (AI-szövegjavaslattal)' : ''), $patient->id);
            return $document;
        });
    }
}
