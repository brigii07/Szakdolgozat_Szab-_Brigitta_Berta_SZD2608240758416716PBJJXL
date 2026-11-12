<?php

namespace App\Http\Controllers;

use App\Http\Requests\Document\AddDocumentRequest;
use App\Http\Requests\Document\DraftDocumentRequest;
use App\Models\Document;
use App\Models\Patient;
use App\Models\PatientAccessLog;
use App\Services\ActivityLogger;
use App\Services\DocumentDraftService;
use App\Services\DocumentService;
use App\Services\PatientAccessLogger;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    public function __construct(private readonly DocumentService $documentService)
    {
    }

    public function createDocument()
    {
        $this->authorize('issue-documents');
        $patients = $this->documentService->selectablePatients();
        return view('documents.create', compact('patients'));
    }

    public function addDocument(AddDocumentRequest $request)
    {
        $this->authorize('issue-documents');
        $document = $this->documentService->addDocument($request->validated(), $request->user());
        return redirect()->route('patients.show', $document->patient_id)->with('success', 'Dokumentum kiállítva.')->with('tab', 'documents');
    }

    public function draftDocument(DraftDocumentRequest $request, DocumentDraftService $draftService)
    {
        set_time_limit(180);
        $this->authorize('issue-documents');
        $patient = Patient::findOrFail($request->validated('patient_id'));

        try {
            $text = $draftService->draft($patient, $request->validated('type'), $request->validated('hint'));
        } catch (\Throwable $e) {
            Log::error('AI dokumentumszöveg-javaslat sikertelen.', ['patient_id' => $patient->id, 'message' => $e->getMessage()]);
            return response()->json([
                'message' => config('app.debug')
                    ? 'AI hiba: ' . $e->getMessage()
                    : 'Nem sikerült szövegjavaslatot készíteni. Próbálja újra, vagy írja meg kézzel.'
            ], 422);
        }

        if ($text === '') {
            return response()->json(['message' => 'Az AI üres választ adott. Próbálja újra.'], 422);
        }

        ActivityLogger::log('AI dokumentumszöveg-javaslat kérve: ' . $request->validated('type'), $patient->id);
        return response()->json(['text' => $text]);
    }

    public function downloadDocument(Document $document, PatientAccessLogger $accessLogger)
    {
        $this->authorize('download', $document);
        abort_unless($document->patient, 404);
        abort_unless(filled($document->file_path) && Storage::disk('local')->exists($document->file_path), 404);
        $fileName = $document->type . '-' . Str::slug($document->patient->name) . '.pdf';
        $accessLogger->record($document->patient, PatientAccessLog::ACTION_DOCUMENT_DOWNLOAD);
        return Storage::disk('local')->download($document->file_path, $fileName);
    }
}
