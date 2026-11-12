<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CareEpisode extends Model
{
    const UPDATED_AT = null;

    const STATUS_NO_INFORMATION = 'NO_INFORMATION';
    const STATUS_WAITING_FOR_DIAGNOSIS = 'WAITING_FOR_DIAGNOSIS';
    const STATUS_WAITING_AI_APPROVAL = 'WAITING_AI_APPROVAL';
    const STATUS_DIAGNOSED = 'DIAGNOSED';
    const STATUS_CLOSED = 'CLOSED';

    const OPEN_STATUSES = [
        self::STATUS_NO_INFORMATION,
        self::STATUS_WAITING_FOR_DIAGNOSIS,
        self::STATUS_WAITING_AI_APPROVAL,
    ];

    protected $fillable = [
        'patient_id',
        'opened_by',
        'title',
        'status',
        'status_changed_at',
        'opened_at',
        'closed_at',
        'closing_note',
    ];

    protected $casts = [
        'status_changed_at' => 'datetime',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (CareEpisode $episode) {
            if ($episode->isDirty('status')) {
                $episode->status_changed_at = now();
                $episode->closed_at = $episode->isOpen() ? null : ($episode->closed_at ?? now());
            }
        });

        static::saved(function (CareEpisode $episode) {
            $patient = $episode->patient;
            if (!$patient) {
                return;
            }
            $latest = $patient->episodes()->first();
            if ($latest && $latest->id === $episode->id && $patient->status !== $episode->status) {
                $patient->update(['status' => $episode->status]);
            }
        });
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }

    public function requireOpen(string $action = 'Ez a művelet'): void
    {
        abort_if(!$this->isOpen(), 409, $action . ' nem végezhető el lezárt ellátási eseten. Indítson új ellátási esetet.');
    }

    public function requireNotClosed(string $action = 'Ez a művelet'): void
    {
        abort_if($this->status === self::STATUS_CLOSED, 409, $action . ' nem végezhető el véglegesen lezárt ellátási eseten.');
    }

    public function displayTitle(): string
    {
        if (filled($this->title)) {
            return $this->title;
        }
        $diagnosis = $this->diagnoses->sortByDesc('created_at')->first();
        return $diagnosis?->diagnosis ?? 'Ellátási eset';
    }

    public function periodLabel(): string
    {
        $from = $this->opened_at->format('Y.m.d.');
        return $this->closed_at ? "{$from} - {$this->closed_at->format('Y.m.d.')}" : "{$from} - folyamatban";
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function symptoms(): BelongsToMany
    {
        return $this->belongsToMany(Symptom::class, 'symptom_patient', 'episode_id', 'symptom_id')
            ->withPivot('severity', 'detail', 'patient_id', 'created_at');
    }

    public function bloodtests(): HasMany
    {
        return $this->hasMany(Bloodtest::class, 'episode_id')->latest('created_at');
    }

    public function vitalSigns(): HasMany
    {
        return $this->hasMany(VitalSign::class, 'episode_id')->orderByDesc('measured_at');
    }

    public function diagnoses(): HasMany
    {
        return $this->hasMany(Diagnosis::class, 'episode_id');
    }

    public function aiAnalyses(): HasMany
    {
        return $this->hasMany(AiAnalysis::class, 'episode_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'episode_id');
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_NO_INFORMATION => 'Nincs információ',
            self::STATUS_WAITING_FOR_DIAGNOSIS => 'Diagnosztizálásra vár',
            self::STATUS_WAITING_AI_APPROVAL => 'Jóváhagyásra vár',
            self::STATUS_DIAGNOSED => 'Diagnosztizált',
            self::STATUS_CLOSED => 'Lezárva diagnózis nélkül',
            default => $status,
        };
    }
}
