@php
    use App\Models\CareEpisode;
    use App\Models\MedicalHistory;
    use App\Models\Patient;
    use App\Models\PatientAccessLog;
    use App\Models\VitalSign;
@endphp
@extends('layouts.app')

@section('title', $patient->name . ' - MedCare Rendszer')
@section('page-title', 'Páciens részletek')

@php
    $latestDiagnosis = $diagnoses->first();
    $latestBloodtest = $bloodtests->first();
    $latestVitals = $vitalSigns->first();
    $oldSymptomIds = array_map('intval', old('symptom_ids', []));
    $episodeOpen = $episode && $episode->isOpen();
    $episodeLocked = $episode && $episode->status === CareEpisode::STATUS_CLOSED;

    $tabs = [
        'summary' => 'Összefoglaló',
        'symptoms' => 'Tünetek',
        'bloodtest' => 'Vérkép',
        'vitals' => 'Vitális paraméterek',
        'history' => 'Előzmények',
        'notes' => 'Megjegyzések',
        'documents' => 'Dokumentumok',
    ];
    if (auth()->user()->can('view-full-history')) {
        $tabs['access'] = 'Hozzáférések';
    }

    $inputClass = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary';
@endphp

@section('content')
    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-6">
        <div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-4">
            <div>
                <div class="flex items-center gap-3 flex-wrap">
                    <h2 class="text-xl font-semibold">{{ $patient->name }}</h2>
                    <span
                        class="px-2.5 py-1 rounded-full text-xs font-medium {{ Patient::statusBadgeClass($patient->status) }}">
                        {{ Patient::statusLabel($patient->status) }}
                    </span>
                    @if ($patient->is_urgent)
                        <span
                            class="px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-600">SÜRGŐS</span>
                    @endif
                </div>
                <p class="text-sm text-slate-500 mt-1">
                    {{ $patient->birth_date }} ({{ $patient->age }} év) &middot;
                    TAJ: {{ $patient->taj_number }} &middot;
                    Tel: {{ $patient->phone }} &middot; {{ $patient->email }}
                </p>

                @php $allergies = $patient->allergies(); @endphp
                @if ($allergies->isNotEmpty())
                    <p class="mt-2 inline-block rounded-lg bg-red-50 border border-red-200 text-red-700 px-3 py-1.5 text-sm font-medium">
                        ⚠ Allergia: {{ $allergies->implode(' · ') }}
                    </p>
                @endif
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                @can('use-ai-assistant')
                    <a href="{{ route('ai.analyze', $patient) }}"
                       class="bg-primary hover:bg-primary/90 text-white rounded-lg px-4 py-2 text-sm font-medium">
                        {{ $episode && $episode->status === CareEpisode::STATUS_WAITING_AI_APPROVAL
                            ? 'AI javaslat elbírálása' : 'AI Asszisztens megnyitása' }}
                    </a>
                @endcan

                @can('view-full-history')
                    <a href="{{ route('patients.summary', $patient) }}"
                       class="border border-slate-300 rounded-lg px-4 py-2 text-sm font-medium hover:bg-slate-50">
                        Összefoglaló (PDF)
                    </a>
                @endcan
            </div>
        </div>

        <div class="mt-5 pt-5 border-t border-slate-200 flex flex-col lg:flex-row lg:items-center gap-3">
            <div class="flex items-center gap-2 flex-1 min-w-0">
                <span class="text-sm font-medium text-slate-500 shrink-0">Ellátási eset:</span>

                @if ($patient->episodes->isNotEmpty())
                    <form method="GET" class="flex-1 min-w-0 max-w-lg">
                        <select name="episode" onchange="this.form.submit()"
                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            @foreach ($patient->episodes as $ep)
                                <option value="{{ $ep->id }}" @selected($episode && $ep->id === $episode->id)>
                                    {{ $ep->periodLabel() }} — {{ $ep->displayTitle() }}
                                    ({{ CareEpisode::statusLabel($ep->status) }})
                                </option>
                            @endforeach
                        </select>
                    </form>
                @else
                    <span class="text-sm text-slate-400">Még nincs ellátási eset.</span>
                @endif
            </div>

            <div class="flex items-center gap-2 shrink-0">
                @if (!$patient->openEpisode)
                    <form method="POST" action="{{ route('episodes.store', $patient) }}"
                          class="flex items-center gap-2">
                        @csrf
                        <input type="text" name="title" maxlength="255"
                               placeholder="Az eset megnevezése (opcionális)"
                               class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <button type="submit"
                                class="bg-secondary hover:bg-secondary/90 text-white rounded-lg px-4 py-2 text-sm font-medium whitespace-nowrap">
                            + Új ellátási eset
                        </button>
                    </form>
                @elseif ($episodeOpen)
                    @can('manage-diagnosis')
                        <form method="POST" action="{{ route('episodes.close', [$patient, $episode]) }}"
                              onsubmit="return confirm('Biztosan lezárod ezt az esetet diagnózis nélkül?');">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                    class="border border-slate-300 rounded-lg px-3 py-2 text-sm font-medium hover:bg-slate-50 whitespace-nowrap">
                                Eset lezárása
                            </button>
                        </form>
                    @endcan
                @endif
            </div>
        </div>

        @if ($episode && !$episodeOpen)
            <p class="mt-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-700 px-4 py-2.5 text-sm">
                @if ($episodeLocked)
                    Ez az ellátási eset véglegesen lezárult diagnózis nélkül. Az adatai sem új rögzítéssel,
                    sem módosítással nem változtathatók. Új panasz rögzítéséhez indítson új ellátási esetet.
                @else
                    Ez az ellátási eset diagnózissal lezárult, ide már nem rögzíthető új adat.
                    A meglévő diagnózis és a rögzített mérések utólag javíthatók, a javítás naplózásra kerül.
                    Új panasz rögzítéséhez indítson új ellátási esetet.
                @endif
            </p>
        @endif
    </div>

    <div class="flex flex-wrap gap-1 border-b border-slate-200 mb-6 text-sm">
        @foreach ($tabs as $key => $label)
            <button type="button" data-tab="{{ $key }}"
                    class="tab-btn px-4 py-2 font-medium border-b-2 border-transparent text-slate-500 hover:text-textmain">
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div class="tab-panel hidden" data-panel="summary">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <h3 class="font-semibold mb-3">Rögzített tünetek</h3>
                <div class="flex flex-wrap gap-2">
                    @forelse ($symptoms as $symptom)
                        @php $detail = $symptom->formatDetail($symptom->pivot->detail); @endphp
                        <span class="px-3 py-1 rounded-full bg-slate-100 text-sm">
                            {{ $symptom->name }}@if ($detail)
                                : <strong>{{ $detail }}</strong>
                            @endif
                            <span class="text-slate-400">({{ $symptom->pivot->severity }})</span>
                        </span>
                    @empty
                        <p class="text-sm text-slate-400">Ebben az esetben még nincs rögzített tünet.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold">Orvosi diagnózis</h3>
                    @can('manage-diagnosis')
                        @if ($latestDiagnosis)
                            <button type="button" class="text-xs text-primary font-medium"
                                    onclick="document.getElementById('diagnosis-edit-form').classList.toggle('hidden')">
                                Diagnózis szerkesztése
                            </button>
                        @endif
                    @endcan
                </div>
                @if ($latestDiagnosis)
                    <p class="font-medium">{{ $latestDiagnosis->diagnosis }}</p>
                    <p class="text-sm text-slate-500 mt-1">{{ $latestDiagnosis->description }}</p>
                    <p class="text-xs text-slate-400 mt-3">
                        {{ $latestDiagnosis->doctor->name ?? '—' }} &middot;
                        {{ $latestDiagnosis->created_at?->format('Y.m.d.') }}
                    </p>

                    @can('manage-diagnosis')
                        <form id="diagnosis-edit-form" method="POST"
                              action="{{ route('diagnoses.update', [$patient, $latestDiagnosis]) }}"
                              class="hidden space-y-3 mt-4 pt-4 border-t border-slate-200">
                            @csrf
                            @method('PATCH')
                            <input type="text" name="diagnosis" value="{{ $latestDiagnosis->diagnosis }}" required
                                   class="{{ $inputClass }}">
                            <textarea name="description" rows="3" required
                                      class="{{ $inputClass }}">{{ $latestDiagnosis->description }}</textarea>
                            <button type="submit"
                                    class="bg-primary hover:bg-primary/90 text-white rounded-lg px-4 py-2 text-sm font-medium">
                                Mentés
                            </button>
                        </form>
                    @endcan
                @else
                    <p class="text-sm text-slate-400">Ebben az esetben még nincs rögzített diagnózis.</p>
                @endif
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-5 lg:col-span-2">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold">Legutóbbi vitális paraméterek</h3>
                    @if ($latestVitals)
                        <span class="text-xs text-slate-400">
                            {{ $latestVitals->measured_at->format('Y.m.d. H:i') }} &middot;
                            {{ $latestVitals->author->name ?? '—' }}
                        </span>
                    @endif
                </div>
                @if ($latestVitals)
                    <div class="flex flex-wrap gap-2">
                        @foreach (VitalSign::PARAMETERS as $field => [$label, $unit])
                            @continue($latestVitals->{$field} === null)
                            @php $flag = $latestVitals->flag($field); @endphp
                            <span
                                class="px-3 py-1 rounded-full text-sm {{ $flag ? 'bg-red-50 text-red-600' : 'bg-slate-100 text-slate-600' }}">
                                {{ $label }}: {{ $latestVitals->{$field} }} {{ $unit }}@if ($flag)
                                    ({{ mb_strtolower($flag) }})
                                @endif
                            </span>
                        @endforeach
                        @if ($latestVitals->bmi())
                            <span class="px-3 py-1 rounded-full text-sm bg-slate-100 text-slate-600">
                                BMI: {{ $latestVitals->bmi() }}
                            </span>
                        @endif
                    </div>
                @else
                    <p class="text-sm text-slate-400">Ebben az esetben még nincs rögzített mérés.</p>
                @endif
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-5 lg:col-span-2">
                <h3 class="font-semibold mb-3">Vérkép összesítő</h3>
                @if ($latestBloodtest)
                    <div class="flex flex-wrap gap-2">
                        @foreach ($latestBloodtest->results as $result)
                            <span
                                class="px-3 py-1 rounded-full text-sm {{ $result->isOutOfRange() ? 'bg-red-50 text-red-600' : 'bg-slate-100 text-slate-600' }}">
                                {{ $result->parameter_name }}: {{ $result->value }} {{ $result->unit }}@if ($result->isOutOfRange())
                                    (eltér)
                                @endif
                            </span>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-slate-400">Ebben az esetben még nincs rögzített vérkép.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="tab-panel hidden" data-panel="symptoms">
        <div class="bg-white rounded-xl border border-slate-200 p-5 max-w-2xl">
            <h3 class="font-semibold mb-4">Tünet rögzítése</h3>

            @if (!$episodeOpen)
                <div class="rounded-lg border border-amber-200 bg-amber-50 text-amber-700 px-4 py-3 text-sm">
                    Ehhez a lezárt esethez már nem rögzíthető tünet.
                    Új panasz rögzítéséhez indítson új ellátási esetet a fejlécben.
                </div>
            @else
                <form method="POST" action="{{ route('symptoms.store', $patient) }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="_tab" value="symptoms">
                    <div>
                        <label class="block text-sm font-medium mb-2">Gyakori tünetek</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2">
                            @foreach ($allSymptoms as $symptom)
                                @php $checked = in_array($symptom->id, $oldSymptomIds, true); @endphp
                                <div>
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="symptom_ids[]" value="{{ $symptom->id }}"
                                               @checked($checked)
                                               class="symptom-checkbox rounded border-slate-300 text-primary focus:ring-primary"
                                               @if ($symptom->detail_type) data-detail-target="symptom-detail-{{ $symptom->id }}" @endif>
                                        {{ $symptom->name }}
                                    </label>

                                    @if ($symptom->detail_type)
                                        <div id="symptom-detail-{{ $symptom->id }}"
                                             class="{{ $checked ? '' : 'hidden' }} mt-1.5 ml-6 flex items-center gap-2">
                                            <input type="{{ $symptom->detail_type === 'number' ? 'number' : 'text' }}"
                                                   name="symptom_details[{{ $symptom->id }}]"
                                                   value="{{ old('symptom_details.' . $symptom->id) }}"
                                                   placeholder="{{ $symptom->detail_label }}"
                                                   @if ($symptom->detail_type === 'number')
                                                       step="0.1" min="{{ $symptom->detail_min }}"
                                                   max="{{ $symptom->detail_max }}"
                                                   @endif
                                                   @disabled(!$checked) @required($checked)
                                                   class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                                            @if ($symptom->detail_unit)
                                                <span class="text-sm text-slate-500">{{ $symptom->detail_unit }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Egyéb tünet</label>
                        <input type="text" name="other_symptom" value="{{ old('other_symptom') }}"
                               placeholder="Írjon be egyéb tünetet..." class="{{ $inputClass }}">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Súlyosság</label>
                        <select name="severity" required class="{{ $inputClass }}">
                            @foreach (['enyhe' => 'Enyhe', 'közepes' => 'Közepes', 'súlyos' => 'Súlyos'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('severity') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-slate-400 mt-1">
                            A kiválasztott súlyosság az összes bejelölt tünetre vonatkozik.
                        </p>
                    </div>

                    <button type="submit"
                            class="bg-primary hover:bg-primary/90 text-white rounded-lg px-5 py-2.5 text-sm font-medium">
                        Mentés
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="tab-panel hidden" data-panel="bloodtest">
        @if ($episodeOpen)
            <div class="bg-white rounded-xl border border-slate-200 p-5 mb-6">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div>
                        <h3 class="font-semibold">Vérkép beolvasása leletről (AI)</h3>
                        <p class="text-xs text-slate-400 mt-1">
                            Töltse fel a laborlelet fotóját vagy PDF-jét. Az AI kitölti az alábbi űrlapot,
                            amelyet mentés előtt ellenőrizni kell.
                        </p>
                    </div>
                    <form id="bloodtest-extract-form" action="{{ route('bloodtests.extract', $patient) }}"
                          class="flex flex-wrap items-center gap-2">
                        @csrf
                        <input type="file" name="file" id="bloodtest-file" required
                               accept="image/jpeg,image/png,image/webp,application/pdf"
                               class="text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium">
                        <button type="submit" id="bloodtest-extract-btn"
                                class="bg-primary hover:bg-primary/90 text-white rounded-lg px-4 py-2 text-sm font-medium disabled:opacity-50 disabled:cursor-wait">
                            Beolvasás AI-val
                        </button>
                    </form>
                </div>
                <p id="bloodtest-extract-status" class="hidden mt-3 text-sm"></p>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-5 mb-6">
                <h3 class="font-semibold mb-4">Vérkép rögzítése</h3>

                <div id="ai-review-banner"
                     class="hidden mb-4 rounded-lg border border-violet-200 bg-violet-50 text-violet-700 px-4 py-3 text-sm">
                    <strong>Az adatokat az AI töltötte ki.</strong>
                    Mentés előtt vesse össze minden sort a lelettel. A sárga sorokat az AI bizonytalannak jelölte,
                    a piros keretes mezőket nem tudta kiolvasni.
                    <p id="ai-review-notes" class="mt-1 text-xs"></p>
                </div>

                <div class="flex flex-col xl:flex-row gap-6">
                    <form method="POST" action="{{ route('bloodtests.store', $patient) }}" id="bloodtest-form"
                          class="space-y-4 flex-1 min-w-0">
                        @csrf
                        <input type="hidden" name="_tab" value="bloodtest">
                        <input type="hidden" name="source" id="bloodtest-source" value="manual">

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                <tr class="text-left text-slate-400">
                                    <th class="pb-2">Paraméter</th>
                                    <th class="pb-2">Érték</th>
                                    <th class="pb-2">Mértékegység</th>
                                    <th class="pb-2">Min.</th>
                                    <th class="pb-2">Max.</th>
                                    <th class="pb-2"></th>
                                </tr>
                                </thead>
                                <tbody id="bloodtest-rows"></tbody>
                            </table>
                        </div>

                        <button type="button" id="add-bloodtest-row" class="text-primary text-sm font-medium">
                            + Új sor
                        </button>

                        <label id="ai-review-confirm" class="hidden flex items-center gap-2 text-sm font-medium">
                            <input type="checkbox" name="verified" value="1" id="ai-review-checkbox"
                                   class="rounded border-slate-300 text-primary focus:ring-primary">
                            Ellenőriztem, hogy az adatok megegyeznek a lelettel
                        </label>

                        <div>
                            <button type="submit"
                                    class="bg-primary hover:bg-primary/90 text-white rounded-lg px-5 py-2.5 text-sm font-medium">
                                Mentés
                            </button>
                        </div>
                    </form>

                    <div id="ai-preview" class="hidden xl:w-96 shrink-0">
                        <p class="text-xs text-slate-400 mb-2">Feltöltött lelet</p>
                        <img id="ai-preview-img" alt="Feltöltött lelet"
                             class="hidden w-full max-h-[600px] object-contain rounded-lg border border-slate-200">
                        <iframe id="ai-preview-pdf" title="Feltöltött lelet"
                                class="hidden w-full h-[600px] rounded-lg border border-slate-200"></iframe>
                    </div>
                </div>
            </div>
        @else
            <div class="bg-white rounded-xl border border-slate-200 p-5 mb-6">
                <div class="rounded-lg border border-amber-200 bg-amber-50 text-amber-700 px-4 py-3 text-sm">
                    Ehhez a lezárt esethez már nem rögzíthető vérkép.
                    Új panasz rögzítéséhez indítson új ellátási esetet a fejlécben.
                </div>
            </div>
        @endif

        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="font-semibold mb-4">Az eset vérképei</h3>
            @forelse ($bloodtests as $bloodtest)
                <div class="mb-4 pb-4 border-b border-slate-100 last:border-0">
                    <p class="text-xs text-slate-400 mb-2 flex items-center gap-2">
                        {{ $bloodtest->created_at?->format('Y.m.d. H:i') }}
                        @if ($bloodtest->source === 'ai_import')
                            <span
                                class="px-2 py-0.5 rounded-full bg-violet-100 text-violet-600 text-[10px] font-medium">
                                AI-beolvasás, ellenőrizve
                            </span>
                        @endif
                    </p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($bloodtest->results as $result)
                            <span
                                class="px-3 py-1 rounded-full text-sm {{ $result->isOutOfRange() ? 'bg-red-50 text-red-600' : 'bg-slate-100 text-slate-600' }}">
                                {{ $result->parameter_name }}: {{ $result->value }} {{ $result->unit }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-400">Ebben az esetben még nincs rögzített vérkép.</p>
            @endforelse
        </div>
    </div>

    <div class="tab-panel hidden" data-panel="vitals">
        <div class="bg-white rounded-xl border border-slate-200 p-5 mb-6">
            <h3 class="font-semibold mb-4">Mérés rögzítése</h3>

            @if (!$episodeOpen)
                <div class="rounded-lg border border-amber-200 bg-amber-50 text-amber-700 px-4 py-3 text-sm">
                    Ehhez a lezárt esethez már nem rögzíthető mérés.
                </div>
            @else
                <form method="POST" action="{{ route('vitals.store', $patient) }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="_tab" value="vitals">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        @foreach ([
                            ['systolic', 'Szisztolés (Hgmm)', '1', '120', 'A vérnyomás felső értéke: a szív összehúzódásakor mért nyomás. Normál: 90–139 Hgmm.'],
                            ['diastolic', 'Diasztolés (Hgmm)', '1', '80', 'A vérnyomás alsó értéke: a szív elernyedésekor mért nyomás. Normál: 60–89 Hgmm.'],
                            ['pulse', 'Pulzus (/perc)', '1', '72', 'Szívfrekvencia, a percenkénti szívverések száma. Nyugalmi normál: 60–100/perc.'],
                            ['temperature', 'Testhőmérséklet (°C)', '0.1', '36.6', 'Mért testhőmérséklet. 37,5 °C felett hőemelkedés, 38 °C felett láz.'],
                            ['spo2', 'SpO2 (%)', '1', '98', 'Oxigénszaturáció: a vér hemoglobinjának hány százaléka szállít oxigént, pulzoximéterrel mérve. Normál: 95% felett.'],
                            ['weight', 'Testsúly (kg)', '0.1', '70', 'Aktuális testsúly kilogrammban. A BMI számításához használja a rendszer.'],
                            ['height', 'Testmagasság (cm)', '1', '175', 'Testmagasság centiméterben. Elég egyszer megadni, a BMI-hez a rendszer a legutóbbi ismert értéket használja.'],
                        ] as [$field, $label, $step, $placeholder, $tooltip])
                            <div>
                                <label class="flex items-center gap-1 text-xs font-medium text-slate-500 mb-1">
                                    {{ $label }}
                                    <span class="relative group inline-flex">
                                        <span
                                            class="w-4 h-4 rounded-full bg-slate-200 text-slate-500 text-[10px] font-semibold flex items-center justify-center cursor-help">?</span>
                                        <span
                                            class="pointer-events-none absolute left-1/2 -translate-x-1/2 bottom-full mb-1.5 hidden group-hover:block w-56 rounded-lg bg-slate-800 text-white text-[11px] font-normal leading-snug px-2.5 py-2 shadow-lg z-20">
                                            {{ $tooltip }}
                                        </span>
                                    </span>
                                </label>
                                <input type="number" name="{{ $field }}" step="{{ $step }}" value="{{ old($field) }}"
                                       placeholder="{{ $placeholder }}" title="{{ $tooltip }}"
                                       class="{{ $inputClass }}">
                            </div>
                        @endforeach
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Mérés ideje</label>
                            <input type="datetime-local" name="measured_at"
                                   value="{{ old('measured_at', now()->format('Y-m-d\TH:i')) }}"
                                   max="{{ now()->format('Y-m-d\TH:i') }}" class="{{ $inputClass }}">
                        </div>
                    </div>
                    <p class="text-xs text-slate-400">
                        Csak a ténylegesen mért értékeket töltse ki. A testmagasságot elég egyszer megadni,
                        a BMI-hez a rendszer a legutóbbi ismert értéket használja.
                    </p>
                    <button type="submit"
                            class="bg-primary hover:bg-primary/90 text-white rounded-lg px-5 py-2.5 text-sm font-medium">
                        Mentés
                    </button>
                </form>
            @endif
        </div>

        @if ($vitalSigns->count() >= 2)
            <div class="bg-white rounded-xl border border-slate-200 p-5 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold">Trend</h3>
                    <select id="vital-chart-param" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
                        <option value="bp">Vérnyomás</option>
                        <option value="pulse">Pulzus</option>
                        <option value="temperature">Testhőmérséklet</option>
                        <option value="spo2">SpO2</option>
                        <option value="weight">Testsúly</option>
                    </select>
                </div>
                <canvas id="vital-chart" height="100"></canvas>
            </div>
        @endif

        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="font-semibold mb-4">Az eset mérései</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                    <tr class="text-left text-slate-400">
                        <th class="pb-2 pr-4">Időpont</th>
                        <th class="pb-2 pr-4">Vérnyomás</th>
                        <th class="pb-2 pr-4">Pulzus</th>
                        <th class="pb-2 pr-4">Hőm.</th>
                        <th class="pb-2 pr-4">SpO2</th>
                        <th class="pb-2 pr-4">Súly</th>
                        <th class="pb-2 pr-4">BMI</th>
                        <th class="pb-2 pr-4">Rögzítette</th>
                        <th class="pb-2"></th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse ($vitalSigns as $v)
                        @php $red = fn ($field) => $v->flag($field) ? 'text-red-600 font-semibold' : ''; @endphp
                        <tr>
                            <td class="py-2 pr-4 whitespace-nowrap">{{ $v->measured_at->format('Y.m.d. H:i') }}</td>
                            <td class="py-2 pr-4 {{ ($v->flag('systolic') || $v->flag('diastolic')) ? 'text-red-600 font-semibold' : '' }}">
                                {{ $v->systolic ? $v->systolic . '/' . $v->diastolic : '—' }}
                            </td>
                            <td class="py-2 pr-4 {{ $red('pulse') }}">{{ $v->pulse ?? '—' }}</td>
                            <td class="py-2 pr-4 {{ $red('temperature') }}">{{ $v->temperature ?? '—' }}</td>
                            <td class="py-2 pr-4 {{ $red('spo2') }}">{{ $v->spo2 ?? '—' }}</td>
                            <td class="py-2 pr-4">{{ $v->weight ?? '—' }}</td>
                            <td class="py-2 pr-4">{{ $v->bmi() ?? '—' }}</td>
                            <td class="py-2 pr-4 text-slate-400">{{ $v->author->name ?? '—' }}</td>
                            <td class="py-2">
                                @if (!$episodeLocked && (auth()->user()->isDoctor() || $v->recorded_by === auth()->id()))                                    <form method="POST" action="{{ route('vitals.destroy', [$patient, $v]) }}"
                                          onsubmit="return confirm('Biztosan törlöd ezt a mérést?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Törlés" class="text-slate-400 hover:text-red-500">
                                            ✕
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-4 text-slate-400">Ebben az esetben még nincs rögzített mérés.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-slate-400 mt-3">
                Normál tartományok: vérnyomás 90–139 / 60–89 Hgmm, pulzus 60–100/perc,
                testhőmérséklet 36,0–37,5 °C, SpO2 ≥ 95%.
            </p>
        </div>
    </div>

    <div class="tab-panel hidden" data-panel="history">
        <div class="bg-white rounded-xl border border-slate-200 p-5 mb-6">
            <h3 class="font-semibold mb-4">Ellátási esetek</h3>
            <ul class="divide-y divide-slate-100">
                @forelse ($patient->episodes as $ep)
                    <li class="py-3 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <a href="{{ route('patients.show', ['patient' => $patient, 'episode' => $ep->id]) }}"
                               class="font-medium hover:text-primary {{ $episode && $ep->id === $episode->id ? 'text-primary' : '' }}">
                                {{ $ep->displayTitle() }}
                            </a>
                            <div class="text-xs text-slate-400">
                                {{ $ep->periodLabel() }}
                                @if ($ep->opener) &middot; indította: {{ $ep->opener->name }} @endif
                                @if (filled($ep->closing_note)) &middot; {{ $ep->closing_note }} @endif
                            </div>
                        </div>
                        <span
                            class="px-2 py-0.5 rounded-full text-[11px] font-medium shrink-0 {{ Patient::statusBadgeClass($ep->status) }}">
                            {{ CareEpisode::statusLabel($ep->status) }}
                        </span>
                    </li>
                @empty
                    <li class="py-3 text-sm text-slate-400">Még nincs ellátási eset.</li>
                @endforelse
            </ul>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <h3 class="font-semibold mb-1">Kórelőzmény és állapotváltozások</h3>
                <p class="text-xs text-slate-400 mb-4">
                    Páciensszintű adat: minden ellátási esetnél érvényes.
                </p>

                @forelse ($patient->medicalHistories as $history)
                    @php $canDeleteHistory = auth()->user()->isDoctor() || $history->recorded_by === auth()->id(); @endphp
                    <div class="mb-3 pb-3 border-b border-slate-100 last:border-0">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <span
                                    class="px-2 py-0.5 rounded-full text-[11px] font-medium {{ MedicalHistory::typeBadgeClass($history->type) }}">
                                    {{ MedicalHistory::typeLabel($history->type) }}
                                </span>
                                <p class="font-medium mt-1">{{ $history->title }}</p>
                                @if (filled($history->description))
                                    <p class="text-sm text-slate-500">{{ $history->description }}</p>
                                @endif
                                <p class="text-xs text-slate-400 mt-1">
                                    @if ($history->occurred_at)
                                        Időpont: {{ $history->occurred_at->format('Y.m.d.') }} &middot;
                                    @endif
                                    Rögzítette: {{ $history->author->name ?? '—' }}
                                </p>
                            </div>
                            @if ($canDeleteHistory)
                                <form method="POST" action="{{ route('history.destroy', [$patient, $history]) }}"
                                      onsubmit="return confirm('Biztosan törlöd ezt az előzményt?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Törlés"
                                            class="text-slate-400 hover:text-red-500 text-sm">✕
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">Még nincs rögzített előzmény.</p>
                @endforelse

                <form method="POST" action="{{ route('history.store', $patient) }}"
                      class="space-y-3 mt-4 pt-4 border-t border-slate-200">
                    @csrf
                    <input type="hidden" name="_tab" value="history">
                    <h4 class="text-sm font-semibold">Új előzmény rögzítése</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <select name="type" required class="{{ $inputClass }}">
                            @foreach (MedicalHistory::TYPES as $type)
                                <option value="{{ $type }}" @selected(old('type') === $type)>
                                    {{ MedicalHistory::typeLabel($type) }}
                                </option>
                            @endforeach
                        </select>
                        <input type="date" name="occurred_at" value="{{ old('occurred_at') }}"
                               max="{{ now()->toDateString() }}"
                               title="Mikor történt (opcionális)" class="{{ $inputClass }}">
                    </div>
                    <input type="text" name="title" value="{{ old('title') }}" required
                           placeholder="Pl. Tüdőgyulladás 2023-ban / Fejfájással érkezett, azóta belázasodott"
                           class="{{ $inputClass }}">
                    <textarea name="description" rows="3" placeholder="Részletek (opcionális)"
                              class="{{ $inputClass }}">{{ old('description') }}</textarea>
                    <button type="submit"
                            class="bg-primary hover:bg-primary/90 text-white rounded-lg px-4 py-2 text-sm font-medium">
                        Rögzítés
                    </button>
                </form>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <h3 class="font-semibold mb-1">Az eset diagnózisai</h3>
                <p class="text-xs text-slate-400 mb-4">
                    Csak a kiválasztott ellátási esethez rögzített diagnózisok.
                </p>

                @forelse ($diagnoses as $diagnosis)
                    <div class="mb-4 pb-4 border-b border-slate-100 last:border-0">
                        <p class="font-medium">{{ $diagnosis->diagnosis }}</p>
                        <p class="text-sm text-slate-500">{{ $diagnosis->description }}</p>
                        <p class="text-xs text-slate-400 mt-1">
                            {{ $diagnosis->doctor->name ?? '—' }} &middot;
                            {{ $diagnosis->created_at?->format('Y.m.d.') }}
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">Ebben az esetben még nincs rögzített diagnózis.</p>
                @endforelse

                @can('manage-diagnosis')
                    @if ($episodeOpen)
                        <form method="POST" action="{{ route('diagnoses.store', $patient) }}"
                              class="space-y-3 mt-4 pt-4 border-t border-slate-200">
                            @csrf
                            <input type="hidden" name="_tab" value="history">
                            <h4 class="text-sm font-semibold">Új diagnózis rögzítése</h4>
                            <input type="text" name="diagnosis" required placeholder="Diagnózis megnevezése"
                                   class="{{ $inputClass }}">
                            <textarea name="description" required rows="3" placeholder="Leírás"
                                      class="{{ $inputClass }}"></textarea>
                            <button type="submit"
                                    class="bg-primary hover:bg-primary/90 text-white rounded-lg px-4 py-2 text-sm font-medium">
                                Mentés
                            </button>
                        </form>
                    @else
                        <p class="mt-4 pt-4 border-t border-slate-200 text-xs text-slate-400">
                            Lezárt esethez nem rögzíthető új diagnózis.
                        </p>
                    @endif
                @endcan
            </div>
        </div>
    </div>

    <div class="tab-panel hidden" data-panel="notes">
        <div class="bg-white rounded-xl border border-slate-200 p-5 max-w-2xl">
            <h3 class="font-semibold mb-1">Megjegyzések</h3>
            <p class="text-xs text-slate-400 mb-4">Páciensszintű adat, nem egy ellátási esethez tartozik.</p>

            @forelse ($patient->notes->sortByDesc('created_at') as $note)
                @php $canManageNote = $note->canBeManagedBy(auth()->user()); @endphp
                <div class="mb-3 pb-3 border-b border-slate-100 last:border-0">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-sm flex-1">{{ $note->note }}</p>
                        @if ($canManageNote)
                            <div class="flex items-center gap-2 text-slate-400 shrink-0">
                                <button type="button" title="Szerkesztés" class="hover:text-primary"
                                        onclick="document.getElementById('note-edit-{{ $note->id }}').classList.toggle('hidden')">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24"
                                         fill="none" stroke="currentColor" stroke-width="2"
                                         stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                </button>
                                <form method="POST" action="{{ route('notes.destroy', [$patient, $note]) }}"
                                      onsubmit="return confirm('Biztosan törlöd ezt a megjegyzést?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Törlés" class="hover:text-red-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24"
                                             fill="none" stroke="currentColor" stroke-width="2"
                                             stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"/>
                                            <path
                                                d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                    <p class="text-xs text-slate-400 mt-1">
                        {{ $note->author->name ?? $note->doctor->name ?? '—' }}
                        @if ($note->author_id && $note->author_id !== $note->doctor_id)
                            (felelős orvos: {{ $note->doctor->name ?? '—' }})
                            @endif
                            &middot; {{ $note->created_at?->format('Y.m.d.') }}
                    </p>

                    @if ($canManageNote)
                        <form id="note-edit-{{ $note->id }}" method="POST"
                              action="{{ route('notes.update', [$patient, $note]) }}" class="hidden mt-2 space-y-2">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="_tab" value="notes">
                            <textarea name="note" rows="2" required
                                      class="{{ $inputClass }}">{{ $note->note }}</textarea>
                            <button type="submit"
                                    class="bg-primary hover:bg-primary/90 text-white rounded-lg px-3 py-1.5 text-xs font-medium">
                                Mentés
                            </button>
                        </form>
                    @endif
                </div>
            @empty
                <p class="text-sm text-slate-400">Még nincs megjegyzés.</p>
            @endforelse

            <form method="POST" action="{{ route('notes.store', $patient) }}"
                  class="space-y-3 mt-4 pt-4 border-t border-slate-200">
                @csrf
                <input type="hidden" name="_tab" value="notes">
                <textarea name="note" required rows="3" placeholder="Új megjegyzés..."
                          class="{{ $inputClass }}"></textarea>
                <button type="submit"
                        class="bg-primary hover:bg-primary/90 text-white rounded-lg px-4 py-2 text-sm font-medium">
                    Hozzáadás
                </button>
            </form>
        </div>
    </div>

    <div class="tab-panel hidden" data-panel="documents">
        <div class="bg-white rounded-xl border border-slate-200 p-5 max-w-2xl">
            <h3 class="font-semibold mb-1">Kiállított dokumentumok</h3>
            <p class="text-xs text-slate-400 mb-4">A kiválasztott ellátási esethez tartozó dokumentumok.</p>

            @forelse ($documents as $document)
                <div class="mb-3 pb-3 border-b border-slate-100 last:border-0 flex items-center justify-between gap-3">
                    <span class="text-sm min-w-0">
                        {{ $document->type }}
                        @if ($document->ai_drafted)
                            <span
                                class="ml-1 px-2 py-0.5 rounded-full bg-violet-100 text-violet-600 text-[10px] font-medium">
                                AI-szövegjavaslattal
                            </span>
                        @endif
                        @if ($document->valid_from && $document->valid_to)
                            <span class="block text-xs text-slate-400 mt-0.5">
                                {{ $document->valid_from->format('Y.m.d.') }} –
                                {{ $document->valid_to->format('Y.m.d.') }}
                                ({{ $document->durationInDays() }} nap)
                            </span>
                        @endif
                    </span>
                    <span class="flex items-center gap-3 shrink-0">
                        <span class="text-xs text-slate-400">{{ $document->created_at?->format('Y.m.d.') }}</span>
                        <a href="{{ route('documents.download', $document) }}"
                           class="text-primary text-xs font-medium hover:underline">Letöltés (PDF)</a>
                    </span>
                </div>
            @empty
                <p class="text-sm text-slate-400">Ebben az esetben még nincs kiállított dokumentum.</p>
            @endforelse

            @can('issue-documents')
                <a href="{{ route('documents.create') }}" class="inline-block mt-4 text-primary text-sm font-medium">
                    + Dokumentum kiállítása
                </a>
            @endcan
        </div>
    </div>

    @can('view-full-history')
        <div class="tab-panel hidden" data-panel="access">
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <h3 class="font-semibold mb-1">Hozzáférési napló</h3>
                <p class="text-xs text-slate-400 mb-4">
                    Ki és mikor nyitotta meg vagy töltötte le a páciens adatait (legutóbbi 50 bejegyzés).
                    Az 5 percen belüli ismételt megnyitás egy bejegyzésnek számít.
                </p>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                        <tr class="text-left text-slate-400">
                            <th class="pb-2 pr-4">Időpont</th>
                            <th class="pb-2 pr-4">Felhasználó</th>
                            <th class="pb-2 pr-4">Művelet</th>
                            <th class="pb-2">IP-cím</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        @forelse ($accessLogs as $log)
                            <tr>
                                <td class="py-2 pr-4 whitespace-nowrap">{{ $log->created_at->format('Y.m.d. H:i') }}</td>
                                <td class="py-2 pr-4">
                                    @if ($log->user)
                                        {{ $log->user->name }}
                                        <span class="text-xs text-slate-400">
                                            ({{ $log->user->isDoctor() ? 'orvos' : 'asszisztens' }})
                                        </span>
                                    @else
                                        <span class="text-slate-400">Törölt felhasználó</span>
                                    @endif
                                </td>
                                <td class="py-2 pr-4">{{ PatientAccessLog::actionLabel($log->action) }}</td>
                                <td class="py-2 text-slate-400"
                                    title="{{ $log->user_agent }}">{{ $log->ip_address }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-4 text-slate-400">Még nincs naplózott hozzáférés.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endcan

    @php
        $vitalChartData = $vitalSigns->sortBy('measured_at')->values()->map(fn ($v) => [
            'label' => $v->measured_at->format('m.d. H:i'),
            'systolic' => $v->systolic,
            'diastolic' => $v->diastolic,
            'pulse' => $v->pulse,
            'temperature' => $v->temperature,
            'spo2' => $v->spo2,
            'weight' => $v->weight,
        ]);
    @endphp
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

    <script>
        (function () {
            var buttons = document.querySelectorAll('.tab-btn');
            var panels = document.querySelectorAll('.tab-panel');

            function activateTab(target) {
                if (!document.querySelector('.tab-panel[data-panel="' + target + '"]')) target = 'summary';
                buttons.forEach(function (b) {
                    var active = b.dataset.tab === target;
                    b.classList.toggle('border-primary', active);
                    b.classList.toggle('text-primary', active);
                    b.classList.toggle('border-transparent', !active);
                    b.classList.toggle('text-slate-500', !active);
                });
                panels.forEach(function (p) {
                    p.classList.toggle('hidden', p.dataset.panel !== target);
                });
            }

            buttons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    activateTab(btn.dataset.tab);
                });
            });
            activateTab(@json(old('_tab', session('tab', 'summary'))));

            document.querySelectorAll('.symptom-checkbox[data-detail-target]').forEach(function (cb) {
                var box = document.getElementById(cb.dataset.detailTarget);
                if (!box) return;
                var input = box.querySelector('input');
                cb.addEventListener('change', function () {
                    box.classList.toggle('hidden', !cb.checked);
                    input.disabled = !cb.checked;
                    input.required = cb.checked;
                    if (cb.checked) input.focus();
                });
            });

            var FIELDS = ['parameter_name', 'value', 'unit', 'reference_min', 'reference_max'];
            var INPUT_CLASS = 'w-full rounded-lg border px-2 py-1.5';
            var tbody = document.getElementById('bloodtest-rows');
            var rowIndex = 0;

            function addRow(values, fromAi) {
                if (!tbody) return;
                values = values || {};
                var i = rowIndex++;
                var row = document.createElement('tr');
                if (fromAi && values.uncertain) row.className = 'bg-amber-50';

                FIELDS.forEach(function (field) {
                    var td = document.createElement('td');
                    td.className = 'pr-2 py-1';
                    var input = document.createElement('input');
                    input.type = 'text';
                    input.name = 'results[' + i + '][' + field + ']';
                    input.required = true;
                    input.value = (values[field] === null || values[field] === undefined) ? '' : values[field];
                    var missing = fromAi && input.value === '';
                    input.className = INPUT_CLASS + (missing ? ' border-red-400' : ' border-slate-300');
                    td.appendChild(input);
                    row.appendChild(td);
                });

                var tdDel = document.createElement('td');
                var del = document.createElement('button');
                del.type = 'button';
                del.title = 'Sor törlése';
                del.textContent = '✕';
                del.className = 'text-slate-400 hover:text-red-500 px-2';
                del.addEventListener('click', function () {
                    row.remove();
                });
                tdDel.appendChild(del);
                row.appendChild(tdDel);

                tbody.appendChild(row);
            }

            function setAiReviewMode(enabled, notes) {
                var source = document.getElementById('bloodtest-source');
                if (!source) return;
                source.value = enabled ? 'ai_import' : 'manual';
                document.getElementById('ai-review-banner').classList.toggle('hidden', !enabled);
                document.getElementById('ai-review-notes').textContent = notes || '';
                document.getElementById('ai-review-confirm').classList.toggle('hidden', !enabled);
                var checkbox = document.getElementById('ai-review-checkbox');
                checkbox.required = enabled;
                checkbox.checked = false;
            }

            if (tbody) {
                var oldRows = @json(array_values(old('results', [])));
                var oldSource = @json(old('source', 'manual'));
                if (oldRows.length) {
                    oldRows.forEach(function (r) {
                        addRow(r, false);
                    });
                    if (oldSource === 'ai_import') setAiReviewMode(true, '');
                } else {
                    ['WBC', 'RBC', 'Hemoglobin', 'CRP'].forEach(function (p) {
                        addRow({parameter_name: p}, false);
                    });
                }

                document.getElementById('add-bloodtest-row').addEventListener('click', function () {
                    addRow({}, false);
                });
            }

            var extractForm = document.getElementById('bloodtest-extract-form');
            var extractBtn = document.getElementById('bloodtest-extract-btn');
            var statusEl = document.getElementById('bloodtest-extract-status');

            function setStatus(text, cls) {
                statusEl.textContent = text;
                statusEl.className = 'mt-3 text-sm ' + cls;
            }

            function showPreview(file) {
                var url = URL.createObjectURL(file);
                var isPdf = file.type === 'application/pdf';
                var img = document.getElementById('ai-preview-img');
                var pdf = document.getElementById('ai-preview-pdf');
                img.classList.toggle('hidden', isPdf);
                pdf.classList.toggle('hidden', !isPdf);
                if (isPdf) {
                    pdf.src = url;
                } else {
                    img.src = url;
                }
                document.getElementById('ai-preview').classList.remove('hidden');
            }

            function shrinkImage(file, maxWidth) {
                if (file.type === 'application/pdf' || file.type === 'image/webp') {
                    return Promise.resolve(file);
                }
                return new Promise(function (resolve) {
                    var img = new Image();
                    img.onload = function () {
                        if (img.width <= maxWidth) {
                            URL.revokeObjectURL(img.src);
                            resolve(file);
                            return;
                        }
                        var canvas = document.createElement('canvas');
                        canvas.width = maxWidth;
                        canvas.height = Math.round(img.height * (maxWidth / img.width));
                        canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
                        canvas.toBlob(function (blob) {
                            URL.revokeObjectURL(img.src);
                            resolve(blob ? new File([blob], 'lelet.jpg', {type: 'image/jpeg'}) : file);
                        }, 'image/jpeg', 0.9);
                    };
                    img.onerror = function () {
                        resolve(file);
                    };
                    img.src = URL.createObjectURL(file);
                });
            }

            function translateError(message) {
                if (/execution time|Maximum execution/i.test(message)) {
                    return 'A feldolgozás túllépte az időkorlátot. Próbálja újra egy kisebb vagy élesebb képpel, vagy rögzítse az adatokat kézzel.';
                }
                if (/Failed to fetch|NetworkError/i.test(message)) {
                    return 'Nem sikerült elérni a szervert. Ellenőrizze a kapcsolatot, majd próbálja újra.';
                }
                return message;
            }

            if (extractForm) {
                extractForm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    var file = document.getElementById('bloodtest-file').files[0];
                    if (!file) return;

                    extractBtn.disabled = true;
                    extractBtn.textContent = 'Feldolgozás...';
                    setStatus('Az AI feldolgozza a leletet, ez akár egy percig is eltarthat...', 'text-slate-500');

                    shrinkImage(file, 1600)
                        .then(function (prepared) {
                            var formData = new FormData();
                            formData.append('_token', extractForm.querySelector('input[name="_token"]').value);
                            formData.append('file', prepared);

                            return fetch(extractForm.action, {
                                method: 'POST',
                                headers: {'Accept': 'application/json'},
                                body: formData
                            });
                        })
                        .then(function (res) {
                            return res.json()
                                .catch(function () {
                                    return {};
                                })
                                .then(function (data) {
                                    return {ok: res.ok, data: data};
                                });
                        })
                        .then(function (r) {
                            if (!r.ok) {
                                var msg = (r.data.errors && r.data.errors.file && r.data.errors.file[0])
                                    || r.data.message
                                    || 'Ismeretlen hiba történt.';
                                throw new Error(msg);
                            }
                            tbody.innerHTML = '';
                            rowIndex = 0;
                            r.data.results.forEach(function (row) {
                                addRow(row, true);
                            });
                            setAiReviewMode(true, r.data.notes);
                            showPreview(file);
                            setStatus('Kész: ' + r.data.results.length + ' paraméter beolvasva. Ellenőrizze az adatokat, majd mentsen.', 'text-secondary');
                            document.getElementById('bloodtest-form').scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        })
                        .catch(function (err) {
                            setStatus(translateError(err.message), 'text-red-500');
                        })
                        .finally(function () {
                            extractBtn.disabled = false;
                            extractBtn.textContent = 'Beolvasás AI-val';
                        });
                });
            }

            var vitalData = @json($vitalChartData);
            var vitalCanvas = document.getElementById('vital-chart');
            if (vitalCanvas && window.Chart && vitalData.length >= 2) {
                var SERIES = {
                    bp: [
                        {key: 'systolic', label: 'Szisztolés (Hgmm)', color: '#EF4444'},
                        {key: 'diastolic', label: 'Diasztolés (Hgmm)', color: '#2563EB'}
                    ],
                    pulse: [{key: 'pulse', label: 'Pulzus (/perc)', color: '#F59E0B'}],
                    temperature: [{key: 'temperature', label: 'Testhőmérséklet (°C)', color: '#EF4444'}],
                    spo2: [{key: 'spo2', label: 'SpO2 (%)', color: '#10B981'}],
                    weight: [{key: 'weight', label: 'Testsúly (kg)', color: '#6366F1'}]
                };

                var vitalChart = new Chart(vitalCanvas, {
                    type: 'line',
                    data: {
                        labels: vitalData.map(function (d) {
                            return d.label;
                        }), datasets: []
                    },
                    options: {responsive: true, spanGaps: true, plugins: {legend: {position: 'bottom'}}}
                });

                function renderVitals(param) {
                    vitalChart.data.datasets = SERIES[param].map(function (s) {
                        return {
                            label: s.label,
                            data: vitalData.map(function (d) {
                                return d[s.key];
                            }),
                            borderColor: s.color,
                            backgroundColor: s.color,
                            tension: 0.3
                        };
                    });
                    vitalChart.update();
                }

                document.getElementById('vital-chart-param').addEventListener('change', function (e) {
                    renderVitals(e.target.value);
                });
                renderVitals('bp');
            }
        })();
    </script>
@endsection
