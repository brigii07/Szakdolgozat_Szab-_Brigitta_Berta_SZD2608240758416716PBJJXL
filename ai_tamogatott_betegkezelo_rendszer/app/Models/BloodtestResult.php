<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BloodtestResult extends Model
{
    use HasFactory;

    protected $table = 'bloodtests_result';

    public $timestamps = false;

    protected $fillable = [
        'bloodtest_id',
        'parameter_name',
        'value',
        'unit',
        'reference_min',
        'reference_max',
    ];

    public function bloodtest(): BelongsTo
    {
        return $this->belongsTo(Bloodtest::class);
    }

    public function isOutOfRange(): bool
    {

        return $this->deviationDirection() !== null;
    }

    public function deviationDirection(): ?string
    {
        if (!is_numeric($this->value)) {
            return null;
        }
        $value = (float)$this->value;
        if (is_numeric($this->reference_min) && $value < (float)$this->reference_min) {
            return 'ALACSONY';
        }
        if (is_numeric($this->reference_max) && $value > (float)$this->reference_max) {
            return 'MAGAS';
        }
        return null;
    }

    public function referenceLabel(): string
    {
        $min = is_numeric($this->reference_min) ? $this->reference_min : null;
        $max = is_numeric($this->reference_max) ? $this->reference_max : null;

        return match (true) {
            $min !== null && $max !== null => "{$min}-{$max}",
            $min !== null => "≥ {$min}",
            $max !== null => "≤ {$max}",
            default => 'nincs megadva',
        };
    }

    public function describeForAi(): string
    {
        $direction = $this->deviationDirection();
        $status = $direction === null ? 'NORMÁL' : $direction . ' (referencián kívül)';

        return sprintf(
            '- %s: %s %s | referencia: %s | állapot: %s',
            $this->parameter_name, $this->value, $this->unit, $this->referenceLabel(), $status
        );
    }
}
