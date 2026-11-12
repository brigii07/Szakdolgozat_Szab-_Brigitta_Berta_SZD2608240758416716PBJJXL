@php use App\Models\Document; @endphp
    <!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>{{ $document->type }} - {{ $patient->name }}</title>
    @include('documents.partials.pdf-styles')
    <style>
        .period-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }

        .period-table td {
            border: 1px solid #E5E7EB;
            padding: 10px 14px;
            vertical-align: top;
            width: 50%;
        }

        .period-highlight {
            font-size: 14px;
            font-weight: bold;
            color: #1F2937;
        }
    </style>
</head>
<body>
<div class="letterhead">
    <table>
        <tr>
            <td style="width: 60%;">
                <span class="brand-mark">&#10084;</span>
                <span class="brand-name">&nbsp;MedCare Rendszer</span><br>
                <span class="brand-sub">Orvosi betegellátó és diagnosztikai támogató rendszer</span>
            </td>
            <td class="doc-meta">
                Dokumentum azonosító: #{{ str_pad($document->id, 6, '0', STR_PAD_LEFT) }}<br>
                Kiállítás dátuma: {{ $document->created_at?->format('Y. m. d.') }}
            </td>
        </tr>
    </table>
</div>

<div class="doc-title">{{ $document->type }}</div>

<table class="info-box">
    <tr>
        <td>
            <div class="info-label">Páciens neve</div>
            <div class="info-value">{{ $patient->name }}</div>
            <br>
            <div class="info-label">Születési idő / hely</div>
            <div class="info-value">{{ $patient->birth_date }} &middot; {{ $patient->birth_place }}</div>
            <br>
            <div class="info-label">TAJ-szám</div>
            <div class="info-value">{{ $patient->taj_number }}</div>
        </td>
        <td>
            <div class="info-label">Kiállító orvos</div>
            <div class="info-value">{{ $doctor->name }}</div>
            <br>
            <div class="info-label">Páciens elérhetősége</div>
            <div class="info-value">{{ $patient->phone }}</div>
            <div class="info-value">{{ $patient->email }}</div>
            <br>
            <div class="info-label">Lakcím</div>
            <div class="info-value">{{ $patient->address }}</div>
        </td>
    </tr>
</table>

@if ($document->valid_from && $document->valid_to)
    <div class="section-title">A keresőképtelenség adatai</div>
    <table class="period-table">
        <tr>
            <td>
                <div class="info-label">Keresőképtelenség kezdete</div>
                <div class="period-highlight">{{ $document->valid_from->format('Y. m. d.') }}</div>
                <br>
                <div class="info-label">Várható záró dátum</div>
                <div class="period-highlight">{{ $document->valid_to->format('Y. m. d.') }}</div>
            </td>
            <td>
                <div class="info-label">Igazolt időtartam</div>
                <div class="period-highlight">{{ $document->durationInDays() }} nap</div>
                <br>
                <div class="info-label">Jogcím</div>
                <div class="info-value">{{ $document->incapacity_reason ?? '—' }}</div>
            </td>
        </tr>
    </table>
@endif

@if ($diagnosis)
    <div class="section-title">Kapcsolódó diagnózis</div>
    <div class="content-box">
        <strong>{{ $diagnosis->diagnosis }}</strong><br>
        {{ $diagnosis->description }}
    </div>
@endif

<div class="section-title">Megjegyzés / leírás</div>
<div class="content-box">
    {!! $note ? nl2br(e($note)) : 'Nincs megadott kiegészítő megjegyzés.' !!}
</div>

<table class="signature-row">
    <tr>
        <td>
            <div class="signature-line">
                Orvos aláírása,
                pecsét{{ !empty($doctor->stamp_number) ? ' (' . $doctor->stamp_number . ')' : '' }}            </div>
        </td>
        <td>
            <div class="signature-line">Dátum: {{ $document->created_at?->format('Y. m. d.') }}</div>
        </td>
    </tr>
</table>

@if ($document->type === Document::TYPE_SICK_LEAVE)
    <div class="footer-note" style="margin-top: 20px;">
        Ez az igazolás a munkáltató és a társadalombiztosítási kifizetőhely részére készült.
        A keresőképtelenség felülvizsgálatának joga az orvost illeti meg.
    </div>
@endif

<div class="footer-note">
    Ez a dokumentum a MedCare Rendszer által, elektronikusan került kiállításra.
    &middot; {{ config('app.name', 'MedCare Rendszer') }}
</div>
</body>
</html>
