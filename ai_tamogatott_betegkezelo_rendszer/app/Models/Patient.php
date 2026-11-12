<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'taj_number',
        'name',
        'birth_date',
        'birth_place',
        'gender',
        'phone',
        'email',
        'address',
        'status',
        'is_urgent'
    ];

    protected $casts = [
        'is_urgent' => 'boolean',
        'status_changed_at' => 'datetime'
    ];

    const STATUS_NO_INFORMATION = 'NO_INFORMATION';
    const STATUS_WAITING_FOR_DIAGNOSIS = 'WAITING_FOR_DIAGNOSIS';
    const STATUS_WAITING_AI_APPROVAL = 'WAITING_AI_APPROVAL';
    const STATUS_DIAGNOSED = 'DIAGNOSED';
    const STATUS_CLOSED = 'CLOSED';

    protected static function booted(): void
    {
        static::saving(function (Patient $patient) {
            if ($patient->isDirty('status')) {
                $patient->status_changed_at = now();
            }
        });
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

    public static function statusBadgeClass(string $status): string
    {
        return match ($status) {
            self::STATUS_WAITING_FOR_DIAGNOSIS => 'bg-amber-100 text-amber-600',
            self::STATUS_WAITING_AI_APPROVAL => 'bg-violet-100 text-violet-600',
            self::STATUS_DIAGNOSED => 'bg-secondary/10 text-secondary',
            self::STATUS_CLOSED => 'bg-amber-200 text-slate-600',
            default => 'bg-slate-100 text-slate-500',
        };
    }

    public function medicalHistories(): HasMany
    {
        return $this->hasMany(MedicalHistory::class)
            ->orderByDesc('occurred_at')
            ->orderByDesc('created_at');
    }

    public function getAgeAttribute(): ?int
    {
        try {
            return Carbon::createFromFormat('Y.m.d', $this->birth_date)->age;
        } catch (\Throwable) {
            return null;
        }
    }

    public function genderLabel(): string
    {
        return match ($this->gender) {
            'M' => 'férfi',
            'F' => 'nő',
            default => 'egyéb/nem megadott',
        };
    }

    public function symptoms(): BelongsToMany
    {
        return $this->belongsToMany(Symptom::class, 'symptom_patient')
            ->withPivot('severity', 'detail', 'episode_id', 'created_at');
    }

    public function diagnoses(): HasMany
    {
        return $this->hasMany(Diagnosis::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function bloodtests(): HasMany
    {
        return $this->hasMany(Bloodtest::class);
    }

    public function aiAnalyses(): HasMany
    {
        return $this->hasMany(AiAnalysis::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(LogEntry::class);
    }

    public function vitalSigns(): HasMany
    {
        return $this->hasMany(VitalSign::class)->orderByDesc('measured_at');
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(PatientAccessLog::class);
    }

    public function episodes(): HasMany
    {
        return $this->hasMany(CareEpisode::class)
            ->orderByDesc('opened_at')
            ->orderByDesc('id');
    }

    public function openEpisode(): HasOne
    {
        return $this->hasOne(CareEpisode::class)
            ->whereIn('status', CareEpisode::OPEN_STATUSES)
            ->latestOfMany('id');
    }

    public function requireOpenEpisode(): CareEpisode
    {
        $episode = $this->openEpisode;
        abort_if(!$episode, 409, 'Ehhez a pácienshez nincs nyitott ellátási eset. Indítson újat az adat rögzítése előtt.');
        return $episode;
    }

    public function allergies(): Collection
    {
        return $this->symptoms
            ->filter(fn($symptom) => str_contains(mb_strtolower($symptom->name), 'allergia'))
            ->map(fn($symptom) => $symptom->name . (filled($symptom->pivot->detail) ? ': ' . $symptom->pivot->detail : ''))
            ->unique()
            ->values();
    }
}
