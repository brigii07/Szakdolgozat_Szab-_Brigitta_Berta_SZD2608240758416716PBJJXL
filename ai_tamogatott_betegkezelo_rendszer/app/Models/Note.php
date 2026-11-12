<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Note extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'author_id',
        'note',
    ];

    public function canBeManagedBy(User $user): bool
    {
        return $this->author_id === $user->id || ($user->isDoctor() && $this->doctor_id === $user->id);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
