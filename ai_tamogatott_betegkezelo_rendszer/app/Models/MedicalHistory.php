<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalHistory extends Model
{
    const UPDATED_AT = null;

    const TYPE_PREVIOUS_CONDITION = 'PREVIOUS_CONDITION';
    const TYPE_PROGRESSION = 'PROGRESSION';
    const TYPE_OTHER = 'OTHER';

    const TYPES = [self::TYPE_PREVIOUS_CONDITION, self::TYPE_PROGRESSION, self::TYPE_OTHER];

    protected $fillable = [
        'patient_id',
        'recorded_by',
        'type',
        'title',
        'description',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'date',
    ];

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            self::TYPE_PREVIOUS_CONDITION => 'Korábbi betegség',
            self::TYPE_PROGRESSION => 'Állapotváltozás',
            default => 'Egyéb előzmény',
        };
    }

    public static function typeBadgeClass(string $type): string
    {
        return match ($type) {
            self::TYPE_PREVIOUS_CONDITION => 'bg-blue-100 text-blue-600',
            self::TYPE_PROGRESSION => 'bg-orange-100 text-orange-600',
            default => 'bg-slate-100 text-slate-500',
        };
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
