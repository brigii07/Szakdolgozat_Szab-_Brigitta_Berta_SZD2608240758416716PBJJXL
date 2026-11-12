<?php

namespace App\Http\Controllers;

use App\Http\Requests\Note\AddNoteRequest;
use App\Http\Requests\Note\UpdateNoteRequest;
use App\Models\Note;
use App\Models\Patient;
use App\Services\NoteService;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function __construct(private readonly NoteService $noteService)
    {
    }

    public function addNote(AddNoteRequest $request, Patient $patient)
    {
        $this->noteService->addNote($patient, $request->validated('note'), $request->user());
        return redirect()->route('patients.show', $patient)->with('success', 'Megjegyzés elmentve.');
    }

    public function updateNote(UpdateNoteRequest $request, Patient $patient, Note $note)
    {
        abort_unless($this->noteService->canManage($note, $request->user()), 403);
        $this->noteService->updateNote($note, $request->validated('note'));
        return redirect()->route('patients.show', $patient)->with('success', 'Megjegyzés frissítve.');
    }

    public function deleteNote(Request $request, Patient $patient, Note $note)
    {
        abort_unless($this->noteService->canManage($note, $request->user()), 403);
        $this->noteService->deleteNote($note);
        return redirect()->route('patients.show', $patient)->with('success', 'Megjegyzés törölve.');
    }
}
