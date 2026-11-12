<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Symptom extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
        'detail_type',
        'detail_label',
        'detail_unit',
        'detail_min',
        'detail_max',
    ];

    protected $casts = [
        'detail_min' => 'float',
        'detail_max' => 'float',
    ];

    public function formatDetail(?string $detail): ?string
    {
        return blank($detail) ? null : trim($detail . ' ' . ($this->detail_unit ?? ''));
    }

    public function patients(): BelongsToMany
    {
        return $this->belongsToMany(Patient::class, 'symptom_patient')
            ->withPivot('severity', 'detail', 'created_at');
    }
}
