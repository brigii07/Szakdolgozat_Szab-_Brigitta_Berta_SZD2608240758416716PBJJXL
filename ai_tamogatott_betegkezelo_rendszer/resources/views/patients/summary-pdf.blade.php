<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Páciens-összefoglaló - {{ $patient->name }}</title>
    @include('documents.partials.pdf-styles')
    <style>
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 22px;
            font-size: 10.5px;
        }

        .data-table th {
            text-align: left;
            font-size: 8.5px;
            text-transform: uppercase;
            color: #6B7280;
            border-bottom: 1px solid #E5E7EB;
            padding: 5px 6px;
        }

        .data-table td {
            border-bottom: 1px solid #F3F4F6;
            padding: 5px 6px;
            vertical-align: top;
        }

        .data-table tr {
            page-break-inside: avoid;
        }

        .flag {
            color: #DC2626;
            font-weight: bold;
        }

        .alert-box {
            border: 1px solid #FCA5A5;
            background-color: #FEF2F2;
            color: #B91C1C;
            border-radius: 4px;
            padding: 10px 14px;
            margin-bottom: 22px;
        }

        .muted {
            color: #9CA3AF;
            font-style: italic;
            margin-bottom: 22px;
        }

        .confidential {
            text-align: center;
            font-size: 9px;
            color: #B91C1C;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }
    </style>
</head>
<body>
@php
    use App\Models\MedicalHistory;
    use App\Models\VitalSign;
    $allergies = $patient->allergies();
@endphp

<div class="letterhead">
    <table>
        <tr>
            <td style="width: 60%;">
                <span class="brand-mark">&#10084;</span>
                <span class="brand-name">&nbsp;MedCare Rendszer</span><br>
                <span class="brand-sub">Orvosi betegellátó és diagnosztikai támogató rendszer</span>
            </td>
            <td class="doc-meta">
                Készült: {{ now()->format('Y. m. d. H:i') }}<br>
                Készítette: {{ $generatedBy->name }}
            </td>
        </tr>
    </table>
</div>

<div class="confidential">Bizalmas egészségügyi adat</div>
<div class="doc-title">Páciens-összefoglaló</div>

<table class="info-box">
    <tr>
        <td>
            <div class="info-label">Páciens neve</div>
            <div class="info-value">{{ $patient->name }}</div>
            <br>
            <div class="info-label">Születési idő / életkor / nem</div>
            <div class="info-value">
                {{ $patient->birth_date }}@if ($patient->age !== null) &middot; {{ $patient->age }} év
                @endif &middot; {{ $patient->genderLabel() }}
            </div>
            <br>
            <div class="info-label">TAJ-szám</div>
            <div class="info-value">{{ $patient->taj_number }}</div>
        </td>
        <td>
            <div class="info-label">Elérhetőség</div>
            <div class="info-value">{{ $patient->phone }}</div>
            <div class="info-value">{{ $patient->email }}</div>
            <br>
            <div class="info-label">Lakcím</div>
            <div class="info-value">{{ $patient->address }}</div>
        </td>
    </tr>
</table>

<div class="section-title">Allergiák</div>
@if ($allergies->isNotEmpty())
    <div class="alert-box"><strong>&#9888; {{ $allergies->implode(' · ') }}</strong></div>
@else
    <p class="muted">Nincs rögzített allergia.</p>
@endif

<div class="section-title">Diagnózisok</div>
@if ($patient->diagnoses->isNotEmpty())
    <table class="data-table">
        <tr>
            <th style="width: 16%;">Dátum</th>
            <th>Diagnózis</th>
            <th style="width: 22%;">Orvos</th>
        <tr>
            <th style="width: 14%;">Dátum</th>
            <th>Diagnózis</th>
            <th style="width: 24%;">Ellátási eset</th>
            <th style="width: 20%;">Orvos</th>
        </tr>
        @foreach ($patient->diagnoses->sortByDesc('created_at') as $diagnosis)
            <tr>
                <td>{{ $diagnosis->created_at?->format('Y.m.d.') }}</td>
                <td><strong>{{ $diagnosis->diagnosis }}</strong><br>{{ $diagnosis->description }}</td>
                <td>{{ $episodeLabels[$diagnosis->episode_id] ?? '—' }}</td>
                <td>{{ $diagnosis->doctor->name ?? '—' }}</td>
            </tr>
        @endforeach
    </table>
@else
    <p class="muted">Nincs rögzített diagnózis.</p>
@endif

<div class="section-title">Panaszok, tünetek</div>
@if ($patient->symptoms->isNotEmpty())
    <table class="data-table">
        <tr>
            <th>Tünet</th>
            <th style="width: 22%;">Részlet</th>
            <th style="width: 13%;">Súlyosság</th>
            <th style="width: 24%;">Ellátási eset</th>
            <th style="width: 13%;">Rögzítve</th>
        </tr>
        @foreach ($patient->symptoms->sortByDesc('pivot.created_at') as $symptom)
            <tr>
                <td>{{ $symptom->name }}</td>
                <td>{{ $symptom->formatDetail($symptom->pivot->detail) ?? '—' }}</td>
                <td>{{ $symptom->pivot->severity }}</td>
                <td>{{ $episodeLabels[$symptom->pivot->episode_id] ?? '—' }}</td>
                <td>{{ $symptom->pivot->created_at ? \Illuminate\Support\Carbon::parse($symptom->pivot->created_at)->format('Y.m.d.') : '—' }}</td>
            </tr>
        @endforeach
    </table>
@else
    <p class="muted">Nincs rögzített tünet.</p>
@endif

<div class="section-title">Vitális paraméterek (legutóbbi mérések)</div>
@if ($vitals->isNotEmpty())
    <table class="data-table">
        <tr>
            <th>Időpont</th>
            <th>Vérnyomás</th>
            <th>Pulzus</th>
            <th>Hőmérséklet</th>
            <th>SpO2</th>
            <th>Súly</th>
            <th>BMI</th>
        </tr>
        @foreach ($vitals as $v)
            <tr>
                <td>{{ $v->measured_at->format('Y.m.d. H:i') }}</td>
                <td class="{{ ($v->flag('systolic') || $v->flag('diastolic')) ? 'flag' : '' }}">
                    {{ $v->systolic ? $v->systolic . '/' . $v->diastolic . ' Hgmm' : '—' }}
                </td>
                <td class="{{ $v->flag('pulse') ? 'flag' : '' }}">{{ $v->pulse ? $v->pulse . ' /perc' : '—' }}</td>
                <td class="{{ $v->flag('temperature') ? 'flag' : '' }}">{{ $v->temperature ? $v->temperature . ' °C' : '—' }}</td>
                <td class="{{ $v->flag('spo2') ? 'flag' : '' }}">{{ $v->spo2 ? $v->spo2 . ' %' : '—' }}</td>
                <td>{{ $v->weight ? $v->weight . ' kg' : '—' }}</td>
                <td>{{ $v->bmi() ?? '—' }}</td>
            </tr>
        @endforeach
    </table>
@else
    <p class="muted">Nincs rögzített mérés.</p>
@endif

<div class="section-title">
    Legutóbbi laborlelet @if ($latestBloodtest)
        ({{ $latestBloodtest->created_at?->format('Y.m.d.') }})
    @endif
</div>
@if ($latestBloodtest)
    <table class="data-table">
        <tr>
            <th>Paraméter</th>
            <th>Érték</th>
            <th>Referencia</th>
            <th>Állapot</th>
        </tr>
        @foreach ($latestBloodtest->results as $result)
            @php $direction = $result->deviationDirection(); @endphp
            <tr>
                <td>{{ $result->parameter_name }}</td>
                <td class="{{ $direction ? 'flag' : '' }}">{{ $result->value }} {{ $result->unit }}</td>
                <td>{{ $result->referenceLabel() }}</td>
                <td class="{{ $direction ? 'flag' : '' }}">{{ $direction ? mb_strtolower($direction) : 'normál' }}</td>
            </tr>
        @endforeach
    </table>
@else
    <p class="muted">Nincs rögzített laborlelet.</p>
@endif

<div class="section-title">Kórelőzmény és állapotváltozások</div>
@if ($patient->medicalHistories->isNotEmpty())
    <table class="data-table">
        <tr>
            <th style="width: 16%;">Időpont</th>
            <th style="width: 20%;">Típus</th>
            <th>Leírás</th>
        </tr>
        @foreach ($patient->medicalHistories as $history)
            <tr>
                <td>{{ $history->occurred_at?->format('Y.m.d.') ?? '—' }}</td>
                <td>{{ MedicalHistory::typeLabel($history->type) }}</td>
                <td><strong>{{ $history->title }}</strong>@if (filled($history->description))
                        <br>{{ $history->description }}
                    @endif</td>
            </tr>
        @endforeach
    </table>
@else
    <p class="muted">Nincs rögzített előzmény.</p>
@endif

<div class="section-title">Legutóbbi megjegyzések</div>
@if ($notes->isNotEmpty())
    <table class="data-table">
        @foreach ($notes as $note)
            <tr>
                <td style="width: 16%;">{{ $note->created_at?->format('Y.m.d.') }}</td>
                <td>{{ $note->note }}</td>
                <td style="width: 22%;">{{ $note->author->name ?? $note->doctor->name ?? '—' }}</td>
            </tr>
        @endforeach
    </table>
@else
    <p class="muted">Nincs megjegyzés.</p>
@endif

<div class="section-title">Kiállított dokumentumok</div>
@if ($patient->documents->isNotEmpty())
    <table class="data-table">
        @foreach ($patient->documents->sortByDesc('created_at') as $document)
            <tr>
                <td style="width: 16%;">{{ $document->created_at?->format('Y.m.d.') }}</td>
                <td>{{ $document->type }} (#{{ str_pad($document->id, 6, '0', STR_PAD_LEFT) }})</td>
            </tr>
        @endforeach
    </table>
@else
    <p class="muted">Nincs kiállított dokumentum.</p>
@endif

<div class="footer-note">
    Ez az összefoglaló a MedCare Rendszerből, {{ now()->format('Y. m. d. H:i') }} időpontban, {{ $generatedBy->name }}
    kérésére készült.
    Tájékoztató jellegű, nem minősül hivatalos orvosi dokumentumnak. Bizalmas egészségügyi adatot tartalmaz.
</div>
</body>
</html>
