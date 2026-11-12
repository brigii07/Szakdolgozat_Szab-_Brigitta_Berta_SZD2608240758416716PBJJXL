<?php

namespace App\Http\Controllers;

use App\Exceptions\AiServiceUnavailableException;
use App\Http\Requests\AI\AcceptSuggestionRequest;
use App\Http\Requests\AI\RejectSuggestionRequest;
use App\Models\AiAnalysis;
use App\Models\Patient;
use App\Models\PatientAccessLog;
use App\Services\AiAnalysisService;
use App\Services\PatientAccessLogger;
use Illuminate\Http\Request;

class AiAnalysisController extends Controller
{
    public function __construct(private readonly AiAnalysisService $aiAnalysisService)
    {
    }

    public function showAnalysis(Patient $patient, PatientAccessLogger $accessLogger)
    {
        $this->authorize('use-ai-assistant');
        $accessLogger->record($patient, PatientAccessLog::ACTION_AI_ANALYSIS);
        $episode = $patient->openEpisode;
        $episodeSymptomCount = $episode ? $episode->symptoms()->count() : 0;
        $episodeBloodtestCount = $episode ? $episode->bloodtests()->count() : 0;

        $analysis = $this->aiAnalysisService->pendingFor($patient, $episode);
        $history = $this->aiAnalysisService->history($patient);
        return view('patients.ai-assistant', compact('patient', 'episode', 'analysis', 'history', 'episodeSymptomCount', 'episodeBloodtestCount'));
    }

    public function generateAnalysis(Patient $patient, Request $request)
    {
        set_time_limit(180);
        $this->authorize('use-ai-assistant');

        if(!$patient->openEpisode){
            return redirect()->route('ai.analyze', $patient)->withErrors([
               'ai' => 'Ehhez a pácienshez nincs nyitott ellátási eset. Indítson újat a páciens oldalán, mielőtt elemzést kér.',
            ]);
        }

        try {
            $analysis = $this->aiAnalysisService->generateAnalysis($patient, $request->user());
        } catch (AiServiceUnavailableException) {
            return redirect()->route('ai.analyze', $patient)->withErrors([
                'ai' => 'Az AI szolgáltatás jelenleg nem elérhető, vagy hiba történt a javaslat lekérése közben. Próbálja újra, vagy rögzítse a diagnózist kézzel.',
            ]);
        }

        if (!$analysis) {
            return redirect()->route('ai.analyze', $patient)->withErrors([
                'ai' => 'Nincs elegendő adat az elemzéshez: rögzítsen legalább egy tünetet vagy vérképet.',
            ]);
        }

        return redirect()->route('ai.analyze', $patient)->with('success', 'Az AI elemzés elkészült.');
    }

    public function acceptSuggestion(AcceptSuggestionRequest $request, Patient $patient, AiAnalysis $aiAnalysis)
    {
        $this->authorize('use-ai-assistant');
        if (!$aiAnalysis->isPending()) {
            return $this->alreadyDecided($patient);
        }

        $this->aiAnalysisService->acceptSuggestion($patient, $aiAnalysis, $request->validated(), $request->user());
        return redirect()->route('patients.show', $patient)->with('success', 'AI javaslat elfogadva, diagnózis rögzítve.');
    }

    public function rejectSuggestion(RejectSuggestionRequest $request, Patient $patient, AiAnalysis $aiAnalysis)
    {
        $this->authorize('use-ai-assistant');
        if (!$aiAnalysis->isPending()) {
            return $this->alreadyDecided($patient);
        }

        $this->aiAnalysisService->rejectSuggestion($patient, $aiAnalysis, $request->validated('rejection_reason'), $request->user());
        return redirect()->route('patients.show', $patient)->with('success', 'AI javaslat elutasítva');
    }

    private function alreadyDecided(Patient $patient)
    {
        return redirect()->route('ai.analyze', $patient)->withErrors([
            'ai' => 'Erről az elemzésről már döntés született, vagy közben újabb elemzés készült.',
        ]);
    }
}
