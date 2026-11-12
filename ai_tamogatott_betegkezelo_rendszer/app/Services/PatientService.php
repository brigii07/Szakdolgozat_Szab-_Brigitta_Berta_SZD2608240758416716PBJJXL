<?php

namespace App\Services;

use App\Models\CareEpisode;
use App\Models\Patient;
use App\Models\Symptom;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PatientService
{
    public function __construct(private readonly CareEpisodeService $episodeService)
    {
    }

    public function listPatient(Request $request)
    {
        return Patient::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = $request->string('q');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('taj_number', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn($query) => $query->where('status', $request->string('status')))
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();
    }

    public function addPatient(array $data, Authenticatable $user): Patient
    {
        return DB::transaction(function () use ($data, $user) {
            $data['status'] = Patient::STATUS_NO_INFORMATION;
            $patient = Patient::create($data);
            ActivityLogger::log('Páciens felvétele: ' . $patient->name, $patient->id);
            $this->episodeService->openEpisode($patient, 'Első jelentkezés', $user);
            return $patient;
        });
    }

    public function detailsPatient(Patient $patient, ?CareEpisode $episode = null): array
    {
        $patient->load([
            'notes.doctor', 'notes.author', 'medicalHistories.author', 'episodes.diagnoses', 'episodes.opener', 'openEpisode',
        ]);
        $episode = $episode ?? $patient->openEpisode ?? $patient->episodes->first();

        $symptoms = $episode ? $episode->symptoms()->get() : collect();
        $vitalSigns = $episode ? $episode->vitalSigns()->with('author')->get() : collect();
        $diagnoses = $episode ? $episode->diagnoses()->with('doctor')->latest('created_at')->get() : collect();
        $documents = $episode ? $episode->documents()->latest('created_at')->get() : collect();

        $bloodtests = collect();
        if ($episode) {
            $query = $episode->bloodtests()->with('results');
            $bloodtests = Gate::allows('view-full-history') ? $query->get() : $query->take(1)->get();
        }

        $allSymptoms = Symptom::orderBy('name')->get();
        $accessLogs = Gate::allows('view-full-history')
            ? $patient->accessLogs()->with('user:id,name,role')->latest('created_at')->take(50)->get()
            : collect();

        return compact('patient', 'episode', 'symptoms', 'vitalSigns', 'diagnoses', 'documents', 'bloodtests', 'allSymptoms', 'accessLogs');
    }


    public function updatePatient(Patient $patient, array $data): Patient
    {
        $patient->update($data);
        ActivityLogger::log('Páciens adatai módosítva: ' . $patient->name, $patient->id);
        return $patient;
    }

    public function deletePatient(Patient $patient): void
    {
        $name = $patient->name;
        $patient->delete();
        ActivityLogger::log('Páciens törölve: ' . $name);
    }
}
