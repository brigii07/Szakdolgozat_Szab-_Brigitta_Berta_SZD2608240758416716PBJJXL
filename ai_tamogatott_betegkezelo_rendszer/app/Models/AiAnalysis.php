<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAnalysis extends Model
{
    use HasFactory;

    const UPDATED_AT = null;
    const STATUS_PENDING = 'PENDING';
    const STATUS_ACCEPTED = 'ACCEPTED';
    const STATUS_REJECTED = 'REJECTED';
    const STATUS_SUPERSEDED = 'SUPERSEDED';

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'diagnosis_id',
        'episode_id',
        'status',
        'suggestions',
        'input_snapshot',
        'model',
        'selected_diagnosis',
        'selected_rank',
        'summary',
        'recommendation',
        'confidence',
        'rejection_reason',
        'decided_by',
        'decided_at',
    ];

    protected $casts = [
        'suggestions' => 'array',
        'input_snapshot' => 'array',
        'confidence' => 'integer',
        'selected_rank' => 'integer',
        'decided_at' => 'datetime',
    ];

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => 'Elbírálásra vár',
            self::STATUS_ACCEPTED => 'Elfogadva',
            self::STATUS_REJECTED => 'Elutasítva',
            self::STATUS_SUPERSEDED => 'Felülírva újabb elemzéssel',
            default => $status,
        };
    }

    public static function statusBadgeClass(string $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => 'bg-violet-100 text-violet-600',
            self::STATUS_ACCEPTED => 'bg-secondary/10 text-secondary',
            self::STATUS_REJECTED => 'bg-red-100 text-red-600',
            default => 'bg-slate-100 text-slate-500',
        };
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function diagnosis(): BelongsTo
    {
        return $this->belongsTo(Diagnosis::class);
    }

    public function episode(): BelongsTo
    {
        return $this->belongsTo(CareEpisode::class, 'episode_id');
    }
}
