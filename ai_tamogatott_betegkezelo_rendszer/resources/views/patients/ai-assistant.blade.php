@php use App\Models\AiAnalysis; @endphp
@extends('layouts.app')

@section('title', 'AI Asszisztens - ' . $patient->name)
@section('page-title', 'AI Asszisztens')

@php
    $suggestions = $analysis?->suggestions ?? [];
    $snapshot = $analysis?->input_snapshot ?? [];
    $first = $suggestions[0] ?? null;
    $inputClass = 'w-full rounded-lg border border-slate-300 px-3 py-2 text-sm';
@endphp

@section('content')
    <div
        class="bg-white rounded-xl border border-slate-200 p-5 mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="font-semibold">Páciens: {{ $patient->name }} ({{ $patient->birth_date }})</h2>
            @if ($episode)
                <p class="text-sm font-medium text-primary mt-1">
                    Elemzett ellátási eset: {{ $episode->displayTitle() }}
                    <span class="font-normal text-slate-400">({{ $episode->periodLabel() }})</span>
                </p>
            @else
                <p class="text-sm font-medium text-amber-600 mt-1">
                    Nincs nyitott ellátási eset — elemzés indítása előtt nyisson egyet a páciens oldalán.
                </p>
            @endif
            @if ($analysis)
                <p class="text-sm text-slate-500 mt-1">
                    Figyelembe vett tünetek: {{ collect($snapshot['symptoms_used'] ?? [])->implode(', ') ?: '—' }}
                </p>
                @if (!empty($snapshot['abnormal_results_used']))
                    <p class="text-sm text-red-500 mt-1">
                        Eltérő laborértékek: {{ implode(', ', $snapshot['abnormal_results_used']) }}
                    </p>
                @endif
                <p class="text-xs text-slate-400 mt-1">
                    Elemzés készült: {{ $analysis->created_at->format('Y.m.d. H:i') }}
                    &middot; kérte: {{ $analysis->doctor->name ?? '—' }}
                    &middot; modell: {{ $analysis->model ?? '—' }}
                </p>
            @endif
        </div>
        <div class="flex items-center gap-4 shrink-0">
            <a href="{{ route('patients.show', $patient) }}" class="text-sm text-primary font-medium">Vissza a
                pácienshez</a>
            <form method="POST" action="{{ route('ai.generate', $patient) }}" id="ai-generate-form"
                  data-confirm="{{ $analysis ? '1' : '0' }}">
                @csrf
                <button type="submit" @disabled(!$episode)
                class="bg-primary hover:bg-primary/90 text-white rounded-lg px-4 py-2 text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                        @if (!$episode) title="Nincs nyitott ellátási eset" @endif>
                    Elemzés indítása
                </button>
            </form>
        </div>
    </div>

    @unless ($analysis)
        <div class="bg-white rounded-xl border border-slate-200 p-8 mb-6 text-center">
            <p class="font-medium">Ehhez a pácienshez nincs elbírálásra váró AI javaslat.</p>
            <p class="text-sm text-slate-500 mt-2 max-w-xl mx-auto">
                Az elemzés az aktuális ellátási eset tüneteit, legutóbbi vérképét és vitális paramétereit,
                valamint a páciens kórelőzményét veszi alapul. A korábbi, lezárt esetek diagnózisai
                előzményként szerepelnek, nem aktuális tünetként.
                Ebben az esetben rögzítve: {{ $episodeSymptomCount }} tünet &middot; {{ $episodeBloodtestCount }}
                vérkép.
            </p>
        </div>
    @else
        <div class="bg-white rounded-xl border border-slate-200 p-5 mb-6">
            <h3 class="font-semibold mb-4">Lehetséges betegségek (AI javaslat)</h3>
            <div class="space-y-3">
                @foreach ($suggestions as $index => $suggestion)
                    <div
                        class="suggestion-card border border-slate-200 rounded-lg p-4 cursor-pointer hover:border-primary"
                        data-index="{{ $index }}" title="Kattintson ide a javaslat kiválasztásához">
                        <div class="flex items-center justify-between">
                            <span class="font-medium">{{ $index + 1 }}. {{ $suggestion['name'] }}</span>
                            <span class="text-sm text-slate-400">{{ $suggestion['confidence'] }}%</span>
                        </div>
                        <p class="text-sm text-slate-500 mt-1">{{ $suggestion['explanation'] }}</p>
                        @if (!empty($suggestion['lab_consistency']))
                            <p class="text-xs text-slate-400 mt-2">
                                <span class="font-medium">Laborértékek:</span> {{ $suggestion['lab_consistency'] }}
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl border border-secondary/30 p-5">
                <h3 class="font-semibold mb-4 text-secondary">AI javaslat elfogadása</h3>
                <p class="text-xs text-slate-400 mb-3">
                    Az elfogadás új diagnózist rögzít a páciensnél a lenti adatokkal.
                </p>
                <form method="POST" action="{{ route('ai.accept', [$patient, $analysis]) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium mb-1">Diagnózis neve</label>
                        <select id="ai-diagnosis-select" class="{{ $inputClass }}">
                            @foreach ($suggestions as $index => $suggestion)
                                <option value="{{ $index }}"
                                        data-name="{{ $suggestion['name'] }}"
                                        data-summary="{{ $suggestion['explanation'] }}"
                                        data-confidence="{{ $suggestion['confidence'] }}">
                                    {{ $index + 1 }}. {{ $suggestion['name'] }} ({{ $suggestion['confidence'] }}%)
                                </option>
                            @endforeach
                            <option value="custom">Egyéb (kézi megadás)</option>
                        </select>

                        <input type="hidden" name="source" id="ai-source" value="{{ $first ? 'ai' : 'manual' }}">
                        <input type="hidden" name="selected_rank" id="ai-selected-rank" value="{{ $first ? 1 : '' }}">
                        <input type="text" name="custom_diagnosis_name" id="ai-diagnosis-custom"
                               placeholder="Diagnózis megnevezése" class="hidden mt-2 {{ $inputClass }}">

                        <p class="text-xs text-slate-400 mt-1">
                            Másik javaslatra váltva az összefoglaló és a megbízhatóság is frissül. Az adatok szabadon
                            szerkeszthetők.
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Összefoglaló</label>
                        <textarea name="summary" id="ai-summary" required rows="3"
                                  class="{{ $inputClass }}">{{ $first['explanation'] ?? '' }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Javaslat (kezelés / további teendők)</label>
                        <textarea name="recommendation" required rows="2" class="{{ $inputClass }}"></textarea>
                    </div>
                    <p id="ai-confidence-note" class="text-xs text-slate-400">
                        A kiválasztott javaslathoz tartozó modell-megbízhatóságot a rendszer automatikusan
                        rögzíti. Ez a modell saját becslése, nem a diagnózis helyességének mérőszáma.
                    </p>
                    <button type="submit"
                            class="bg-secondary hover:bg-secondary/90 text-white rounded-lg px-5 py-2.5 text-sm font-medium">
                        AI javaslat elfogadása
                    </button>
                </form>
            </div>

            <div class="bg-white rounded-xl border border-red-200 p-5">
                <h3 class="font-semibold mb-4 text-red-500">AI javaslat elutasítása</h3>
                <p class="text-xs text-slate-400 mb-3">
                    Az elutasítás nem hoz létre diagnózist. A páciens visszakerül a „Diagnosztizálásra vár" listába,
                    a diagnózis kézzel rögzíthető az Előzmények fülön.
                </p>
                <form method="POST" action="{{ route('ai.reject', [$patient, $analysis]) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium mb-1">Elutasítás indoklása</label>
                        <textarea name="rejection_reason" required rows="4"
                                  placeholder="Pl. a javaslatok nem egyeztethetők össze a fizikális vizsgálat eredményével."
                                  class="{{ $inputClass }}"></textarea>
                    </div>
                    <button type="submit"
                            class="bg-red-500 hover:bg-red-600 text-white rounded-lg px-5 py-2.5 text-sm font-medium">
                        AI javaslat elutasítása
                    </button>
                </form>
            </div>
        </div>
    @endunless
    @if ($history->isNotEmpty())
        <div class="bg-white rounded-xl border border-slate-200 p-5 mt-6">
            <h3 class="font-semibold mb-4">Korábbi AI elemzések</h3>
            <ul class="divide-y divide-slate-100">
                @foreach ($history as $item)
                    <li class="py-3 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <span
                                    class="px-2 py-0.5 rounded-full text-[11px] font-medium {{ AiAnalysis::statusBadgeClass($item->status) }}">
                                    {{ AiAnalysis::statusLabel($item->status) }}
                                </span>
                                <span
                                    class="ml-2">AI első javaslata: <strong>{{ $item->suggestions[0]['name'] ?? '—' }}</strong></span>
                            </div>
                            <span
                                class="text-xs text-slate-400 shrink-0">{{ $item->created_at->format('Y.m.d. H:i') }}</span>
                        </div>
                        @if ($item->status === AiAnalysis::STATUS_ACCEPTED)
                            <p class="text-xs text-slate-500 mt-1">
                                Elfogadott diagnózis: <strong>{{ $item->selected_diagnosis }}</strong>
                                ({{ $item->selected_rank ? $item->selected_rank . '. AI javaslat' : 'kézi megadás' }})
                                &middot; {{ $item->decider->name ?? '—' }}
                            </p>
                        @elseif ($item->status === AiAnalysis::STATUS_REJECTED)
                            <p class="text-xs text-slate-500 mt-1">
                                Elutasítás indoka:
                                {{ $item->rejection_reason }} &middot; {{ $item->decider->name ?? '—' }}
                            </p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <script>
        (function () {
            var generateForm = document.getElementById('ai-generate-form');
            var generateBtn = document.getElementById('ai-generate-btn');
            generateForm.addEventListener('submit', function (e) {
                if (generateForm.dataset.confirm === '1'
                    && !confirm('Új elemzés készül a jelenlegi adatok alapján. A mostani, még el nem bírált javaslat felülírt állapotba kerül. Folytatja?')) {
                    e.preventDefault();
                    return;
                }
                generateBtn.disabled = true;
                generateBtn.textContent = 'Elemzés folyamatban...';
            });

            var select = document.getElementById('ai-diagnosis-select');
            if (!select) return;

            var hiddenSource = document.getElementById('ai-source');
            var hiddenRank = document.getElementById('ai-selected-rank');
            var customInput = document.getElementById('ai-diagnosis-custom');
            var summary = document.getElementById('ai-summary');

            function applySelection() {
                var option = select.selectedOptions[0];

                if (option.value === 'custom') {
                    customInput.classList.remove('hidden');
                    customInput.required = true;
                    customInput.disabled = false;
                    hiddenSource.value = 'manual';
                    hiddenRank.value = '';
                    summary.value = '';
                    customInput.focus();
                    return;
                }

                customInput.classList.add('hidden');
                customInput.required = false;
                customInput.disabled = true;
                hiddenSource.value = 'ai';
                hiddenRank.value = parseInt(option.value, 10) + 1;
                summary.value = option.dataset.summary;
            }

            select.addEventListener('change', applySelection);
            applySelection();

            select.addEventListener('change', applySelection);

            document.querySelectorAll('.suggestion-card').forEach(function (card) {
                card.addEventListener('click', function () {
                    select.value = card.dataset.index;
                    applySelection();
                    select.scrollIntoView({behavior: 'smooth', block: 'center'});
                });
            });
        })();
    </script>
@endsection
