<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'name',
        'email',
        'password_hash',
        'role',
        'stamp_number',
        'doctor_id',
    ];

    protected $hidden = [
        'password_hash',
    ];

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function isDoctor(): bool
    {
        return $this->role === 'doctor';
    }

    public function isAssistant(): bool
    {
        return $this->role === 'assistant';
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function assistants(): HasMany
    {
        return $this->hasMany(User::class, 'doctor_id');
    }

    public function diagnoses(): HasMany
    {
        return $this->hasMany(Diagnosis::class, 'doctor_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class, 'doctor_id');
    }

    public function bloodtests(): HasMany
    {
        return $this->hasMany(Bloodtest::class, 'doctor_id');
    }

    public function aiAnalyses(): HasMany
    {
        return $this->hasMany(AiAnalysis::class, 'doctor_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'doctor_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(LogEntry::class, 'user_id');
    }

    public function getRememberToken()
    {
        return null;
    }

    public function setRememberToken($value)
    {

    }

    public function getRememberTokenName()
    {
        return '';
    }
}
