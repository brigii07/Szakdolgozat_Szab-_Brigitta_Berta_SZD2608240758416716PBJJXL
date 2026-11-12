<?php

namespace App\Http\Controllers;

use App\Http\Requests\CareEpisode\OpenEpisodeRequest;
use App\Models\CareEpisode;
use App\Models\Patient;
use App\Services\CareEpisodeService;
use Illuminate\Http\Request;

class CareEpisodeController extends Controller
{
    public function __construct(private readonly CareEpisodeService $episodeService)
    {
    }

    public function openEpisode(OpenEpisodeRequest $request, Patient $patient)
    {
        $episode = $this->episodeService->openEpisode($patient, $request->validated('title'), $request->user());
        return redirect()->route('patients.show', ['patient' => $patient, 'episode' => $episode->id])->with('success', 'Új ellátási eset elindítva.');
    }

    public function closeEpisode(Request $request, Patient $patient, CareEpisode $episode)
    {
        $this->authorize('manage-diagnosis');
        abort_unless($episode->isOpen(), 409, 'Ez az eset már lezárult.');
        $this->episodeService->closeEpisode($episode, $request->string('closing_note')->toString() ?: null);
        return redirect()->route('patients.show', ['patient' => $patient, 'episode' => $episode->id])->with('success', 'Az ellátási eset lezárva.');
    }
}
