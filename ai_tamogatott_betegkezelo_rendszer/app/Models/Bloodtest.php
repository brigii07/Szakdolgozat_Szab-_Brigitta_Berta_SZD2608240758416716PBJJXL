<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bloodtest extends Model
{
    use HasFactory;

    const UPDATED_AT = null;
    const SOURCE_MANUAL = 'manual';
    const SOURCE_AI_IMPORT = 'ai_import';

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'episode_id',
        'source',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(BloodtestResult::class);
    }

    public function episode(): BelongsTo
    {
        return $this->belongsTo(CareEpisode::class, 'episode_id');
    }
}
