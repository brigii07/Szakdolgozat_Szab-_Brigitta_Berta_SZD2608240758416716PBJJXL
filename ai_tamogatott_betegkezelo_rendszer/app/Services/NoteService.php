<?php


namespace App\Services;

use App\Models\Note;
use App\Models\Patient;
use Illuminate\Contracts\Auth\Authenticatable;

class NoteService
{
    public function addNote(Patient $patient, string $note, Authenticatable $user): Note
    {
        $created = $patient->notes()->create([
            'note' => $note,
            'doctor_id' => $user->role === 'doctor' ? $user->id : $user->doctor_id,
            'author_id' => $user->id,
        ]);
        ActivityLogger::log('Megjegyzés hozzáadva', $patient->id);
        return $created;
    }

    public function canManage(Note $note, Authenticatable $user): bool
    {
        return $note->canBeManagedBy($user);
    }

    public function updateNote(Note $note, string $text): Note
    {
        $note->update(['note' => $text]);
        ActivityLogger::log('Megjegyzés szerkesztve', $note->patient_id);
        return $note;
    }

    public function deleteNote(Note $note): void
    {
        $patientId = $note->patient_id;
        $note->delete();
        ActivityLogger::log('Megjegyzés törölve', $patientId);
    }
}
