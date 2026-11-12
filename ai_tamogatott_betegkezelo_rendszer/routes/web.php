<?php

use App\Http\Controllers\AiAnalysisController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BloodtestController;
use App\Http\Controllers\CareEpisodeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiagnosisController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\MedicalHistoryController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SymptomController;
use App\Http\Controllers\VitalSignController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1')->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'indexDashboard'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'showProfile'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::get('/patients', [PatientController::class, 'patientIndex'])->name('patients.index');
    Route::get('/patients/new', [PatientController::class, 'createPatient'])->name('patients.create');
    Route::post('/patients', [PatientController::class, 'addPatient'])->name('patients.store');
    Route::get('/patients/{patient}', [PatientController::class, 'showPatient'])->name('patients.show');
    Route::get('/patients/{patient}/edit', [PatientController::class, 'editPatient'])->name('patients.edit');
    Route::put('/patients/{patient}', [PatientController::class, 'updatePatient'])->name('patients.update');

    Route::post('/patients/{patient}/symptoms', [SymptomController::class, 'addSymptoms'])->name('symptoms.store');
    Route::post('/patients/{patient}/bloodtest', [BloodtestController::class, 'addBloodtestResults'])->name('bloodtests.store');
    Route::post('/patients/{patient}/bloodtest/extract', [BloodtestController::class, 'extractFromImage'])->middleware('throttle:10,1')->name('bloodtests.extract');

    Route::post('/patients/{patient}/history', [MedicalHistoryController::class, 'addHistory'])->name('history.store');
    Route::delete('/patients/{patient}/history/{medicalHistory}', [MedicalHistoryController::class, 'deleteHistory'])->scopeBindings()->name('history.destroy');

    Route::post('/patients/{patient}/comment', [NoteController::class, 'addNote'])->name('notes.store');
    Route::patch('/patients/{patient}/comment/{note}', [NoteController::class, 'updateNote'])->scopeBindings()->name('notes.update');
    Route::delete('/patients/{patient}/comment/{note}', [NoteController::class, 'deleteNote'])->scopeBindings()->name('notes.destroy');

    Route::get('/documents/{document}/download', [DocumentController::class, 'downloadDocument'])->name('documents.download');

    Route::post('/patients/{patient}/vitals', [VitalSignController::class, 'addVitalSign'])->name('vitals.store');
    Route::delete('/patients/{patient}/vitals/{vitalSign}', [VitalSignController::class, 'deleteVitalSign'])->scopeBindings()->name('vitals.destroy');

    Route::post('/patients/{patient}/episodes', [CareEpisodeController::class, 'openEpisode'])->name('episodes.store');
    Route::patch('/patients/{patient}/episodes/{episode}/close', [CareEpisodeController::class, 'closeEpisode'])->scopeBindings()->name('episodes.close');

    Route::middleware('role:doctor')->group(function () {
        Route::post('/patients/{patient}/diagnosis', [DiagnosisController::class, 'addDiagnosis'])->name('diagnoses.store');
        Route::patch('/patients/{patient}/diagnosis/{diagnosis}', [DiagnosisController::class, 'updateDiagnosis'])->scopeBindings()->name('diagnoses.update');

        Route::get('/patients/{patient}/ai-analysis', [AiAnalysisController::class, 'showAnalysis'])->name('ai.analyze');
        Route::post('/patients/{patient}/ai-analysis', [AiAnalysisController::class, 'generateAnalysis'])->middleware('throttle:10,1')->name('ai.generate');
        Route::post('/patients/{patient}/ai-analysis/{aiAnalysis}/accept', [AiAnalysisController::class, 'acceptSuggestion'])->scopeBindings()->name('ai.accept');
        Route::post('/patients/{patient}/ai-analysis/{aiAnalysis}/reject', [AiAnalysisController::class, 'rejectSuggestion'])->scopeBindings()->name('ai.reject');

        Route::get('/documents/new', [DocumentController::class, 'createDocument'])->name('documents.create');
        Route::post('/documents', [DocumentController::class, 'addDocument'])->name('documents.store');

        Route::delete('/patients/{patient}', [PatientController::class, 'deletePatient'])->name('patients.destroy');

        Route::get('/patients/{patient}/summary-pdf', [PatientController::class, 'downloadSummary'])->name('patients.summary');
        Route::post('/documents/draft', [DocumentController::class, 'draftDocument'])->middleware('throttle:10,1')->name('documents.draft');
    });
});
