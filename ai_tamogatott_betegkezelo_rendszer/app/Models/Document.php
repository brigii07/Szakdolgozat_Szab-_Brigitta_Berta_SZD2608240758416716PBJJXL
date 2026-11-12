<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'episode_id',
        'type',
        'file_path',
        'content',
        'ai_drafted',
        'valid_from',
        'valid_to',
        'incapacity_reason',
    ];

    protected $casts = [
        'ai_drafted' => 'boolean',
        'valid_from' => 'date',
        'valid_to' => 'date',
    ];
    const TYPE_CERTIFICATE = 'Igazolás';
    const TYPE_REFERRAL = 'Beutaló';
    const TYPE_REPORT = 'Lelet';
    const TYPE_SICK_LEAVE = 'Keresőképtelenségi igazolás';
    const TYPE_OTHER = 'Egyéb';
    const TYPES = [self::TYPE_CERTIFICATE, self::TYPE_SICK_LEAVE, self::TYPE_REFERRAL, self::TYPE_REPORT, self::TYPE_OTHER];

    const INCAPACITY_REASONS = [
        'Saját jogú keresőképtelenség (betegség)',
        'Üzemi baleset',
        'Beteg gyermek ápolása',
        'Veszélyeztetett várandósság',
        'Egyéb',
    ];

    public static function requiresDiagnosedPatient(string $type): bool
    {
        return in_array($type, [self::TYPE_CERTIFICATE, self::TYPE_SICK_LEAVE], true);
    }

    public static function requiresPeriod(string $type): bool
    {
        return $type === self::TYPE_SICK_LEAVE;
    }

    public function durationInDays(): ?int
    {
        return $this->valid_from && $this->valid_to
            ? $this->valid_from->diffInDays($this->valid_to) + 1 : null;
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function episode(): BelongsTo
    {
        return $this->belongsTo(CareEpisode::class, 'episode_id');
    }
}
