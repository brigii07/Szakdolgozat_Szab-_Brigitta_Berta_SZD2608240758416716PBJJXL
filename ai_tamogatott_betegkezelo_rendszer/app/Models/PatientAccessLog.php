<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientAccessLog extends Model
{
    const UPDATED_AT = null;

    const ACTION_VIEW = 'VIEW';
    const ACTION_SUMMARY_PDF = 'SUMMARY_PDF';
    const ACTION_DOCUMENT_DOWNLOAD = 'DOCUMENT_DOWNLOAD';
    const ACTION_AI_ANALYSIS = 'AI_ANALYSIS';

    protected $fillable = [
        'patient_id',
        'user_id',
        'action',
        'ip_address',
        'user_agent',
    ];

    public static function actionLabel(string $action): string
    {
        return match ($action) {
            self::ACTION_VIEW => 'Adatlap megtekintése',
            self::ACTION_SUMMARY_PDF => 'Összefoglaló PDF letöltése',
            self::ACTION_DOCUMENT_DOWNLOAD => 'Dokumentum letöltése',
            self::ACTION_AI_ANALYSIS => 'AI elemzés megnyitása',
            default => $action,
        };
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
