<?php

namespace App\Http\Controllers;

use App\Http\Requests\BloodTest\AddBloodtestResultsRequest;
use App\Http\Requests\Bloodtest\ExtractBloodtestRequest;
use App\Models\Bloodtest;
use App\Models\Patient;
use App\Services\ActivityLogger;
use App\Services\BloodtestExtractionService;
use App\Services\BloodtestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class BloodtestController extends Controller
{
    public function __construct(private readonly BloodtestService $bloodtestService, private readonly BloodtestExtractionService $extractionService)
    {
    }

    public function addBloodtestResults(AddBloodtestResultsRequest $request, Patient $patient)
    {
        $this->bloodtestService->addBloodtestResults($patient, $request->validated('results'), $request->user(), $request->validated('source') ?? Bloodtest::SOURCE_MANUAL);
        return redirect()->route('patients.show', $patient)->with('success', 'Vérkép elmentve')->with('tab', 'bloodtest');
    }

    public function extractFromImage(ExtractBloodtestRequest $request, Patient $patient): JsonResponse
    {
        set_time_limit(180);

        try {
            $data = $this->extractionService->extract($request->file('file'));
        } catch (\Throwable $e) {
            Log::error('Ai vérkép-beolvasás sikertelen.', ['patient_id' => $patient->id, 'message' => $e->getMessage()]);
            return response()->json([
                'message' => 'Az AI nem tudta feldolgozni a fájlt. Próbálja újra, vagy rögzítse az adatokat kézzel.',], 422);
        }

        if (empty($data['results'])) {
            return response()->json([
                'message' => 'A fájlon nem található értelmezhető vérkép-adat.' . ($data['notes'] ? ' (' . $data['notes'] . ')' : ''),], 422);
        }

        ActivityLogger::log('Vérkép AI-beolvasás: ' . count($data['results']) . 'paraméter', $patient->id);
        return response()->json($data);
    }
}
