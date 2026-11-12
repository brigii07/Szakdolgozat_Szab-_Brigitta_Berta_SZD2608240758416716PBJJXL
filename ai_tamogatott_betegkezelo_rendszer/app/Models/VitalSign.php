<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VitalSign extends Model
{
    const UPDATED_AT = null;

    const PARAMETERS = [
        'systolic' => ['Szisztolés vérnyomás', 'Hgmm', 90, 139],
        'diastolic' => ['Diasztolés vérnyomás', 'Hgmm', 60, 89],
        'pulse' => ['Pulzus', '/perc', 60, 100],
        'temperature' => ['Testhőmérséklet', '°C', 36.0, 37.5],
        'spo2' => ['Oxigénszaturáció', '%', 95, 100],
        'weight' => ['Testsúly', 'kg', null, null],
        'height' => ['Testmagasság', 'cm', null, null],
    ];

    protected $fillable = [
        'patient_id',
        'recorded_by',
        'episode_id',
        'systolic',
        'diastolic',
        'pulse',
        'temperature',
        'spo2',
        'weight',
        'height',
        'measured_at',
    ];

    protected $casts = [
        'systolic' => 'integer',
        'diastolic' => 'integer',
        'pulse' => 'integer',
        'temperature' => 'float',
        'spo2' => 'integer',
        'weight' => 'float',
        'height' => 'integer',
        'measured_at' => 'datetime',
    ];

    public function flag(string $field): ?string
    {
        [, , $min, $max] = self::PARAMETERS[$field];
        $value = $this->{$field};

        if ($value === null || $min === null) {
            return null;
        }
        if ($value < $min) {
            return 'ALACSONY';
        }
        if ($value > $max) {
            return 'MAGAS';
        }
        return null;
    }

    public function hasAbnormal(): bool
    {
        foreach (array_keys(self::PARAMETERS) as $field) {
            if ($this->flag($field)) {
                return true;
            }
        }
        return false;
    }

    public function bmi(): ?float
    {
        if (!$this->weight || !$this->height) {
            return null;
        }
        return round($this->weight / (($this->height / 100) ** 2), 1);
    }

    public function describeForAi(): string
    {
        $parts = [];
        foreach (self::PARAMETERS as $field => [$label, $unit]) {
            if ($this->{$field} === null) {
                continue;
            }
            $flag = $this->flag($field);
            $parts[] = "{$label}: {$this->{$field}} {$unit}" . ($flag ? " ({$flag})" : '');
        }
        if ($bmi = $this->bmi()) {
            $parts[] = "BMI: {$bmi}";
        }
        return 'mérés ideje ' . $this->measured_at->format('Y.m.d. H:i') . ': ' . implode('; ', $parts);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function episode(): BelongsTo
    {
        return $this->belongsTo(CareEpisode::class, 'episode_id');
    }
}
