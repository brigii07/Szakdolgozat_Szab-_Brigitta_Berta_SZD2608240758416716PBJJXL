@php use App\Models\Document; use App\Models\Patient; @endphp
@extends('layouts.app')

@section('title', 'Dokumentum kiállítása - MedCare Rendszer')
@section('page-title', 'Dokumentum kiállítása')

@section('content')
    <div class="bg-white rounded-xl border border-slate-200 p-6 max-w-xl mx-auto">
        <p class="text-sm text-slate-400 mb-6">
            Igazolás és keresőképtelenségi igazolás csak diagnózissal lezárt ellátási esethez állítható ki.
            Beutaló, lelet és egyéb dokumentum bármely páciensnek.
        </p>

        <form method="POST" action="{{ route('documents.store') }}" id="document-form" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium mb-1">Dokumentum típusa</label>
                <select name="type" id="document-type" required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                    @foreach (Document::TYPES as $type)
                        <option value="{{ $type }}" @selected(old('type') === $type)
                        data-requires-diagnosis="{{ Document::requiresDiagnosedPatient($type) ? '1' : '0' }}"
                                data-requires-period="{{ Document::requiresPeriod($type) ? '1' : '0' }}">
                            {{ $type }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Páciens</label>
                <select name="patient_id" id="document-patient" required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                    <option value="">Válassz pácienst...</option>
                    @foreach ($patients as $patient)
                        <option value="{{ $patient->id }}" @selected((int) old('patient_id') === $patient->id)
                        data-diagnosed="{{ $patient->status === Patient::STATUS_DIAGNOSED ? '1' : '0' }}">
                            {{ $patient->name }} ({{ $patient->birth_date }})
                            — {{ Patient::statusLabel($patient->status) }}
                        </option>
                    @endforeach
                </select>
                <p id="patient-hint" class="hidden text-xs text-amber-500 mt-1">
                    Ehhez a dokumentumtípushoz csak olyan páciens választható, akinek a legutóbbi ellátási esete
                    diagnózissal lezárult.
                </p>
                @if ($patients->isEmpty())
                    <p class="text-xs text-amber-500 mt-1">Még nincs rögzített páciens a rendszerben.</p>
                @endif
            </div>

            <div id="sick-leave-fields" class="hidden space-y-3 rounded-lg border border-slate-200 p-4">
                <h3 class="text-sm font-semibold">A keresőképtelenség adatai</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Keresőképtelenség kezdete</label>
                        <input type="date" name="valid_from" id="sick-leave-from"
                               value="{{ old('valid_from', now()->toDateString()) }}"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Várható záró dátum</label>
                        <input type="date" name="valid_to" id="sick-leave-to" value="{{ old('valid_to') }}"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Jogcím</label>
                    <select name="incapacity_reason"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        @foreach (Document::INCAPACITY_REASONS as $reason)
                            <option value="{{ $reason }}" @selected(old('incapacity_reason') === $reason)>
                                {{ $reason }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <p id="sick-leave-duration" class="text-xs text-slate-500"></p>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label for="document-note" class="block text-sm font-medium">Megjegyzés / leírás</label>
                    <button type="button" id="ai-draft-btn"
                            class="text-sm text-primary font-medium hover:underline disabled:opacity-50 disabled:cursor-wait">
                        ✨ Szöveg javaslata AI-val
                    </button>
                </div>
                <input type="text" id="ai-draft-hint" maxlength="255"
                       placeholder="Opcionális: cél vagy kulcsszavak az AI-nak (pl. kardiológiai kivizsgálás, 3 nap kímélet)"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm mb-2">
                <textarea name="note" id="document-note" rows="8" maxlength="2000"
                          placeholder="A beteg 2024.05.26-án vizsgálaton vett részt. Kérjük állapotának megfelelő kímélet biztosítását."
                          class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">{{ old('note') }}</textarea>
                <input type="hidden" name="ai_drafted" id="document-ai-drafted" value="{{ old('ai_drafted', 0) }}">
                <p id="ai-draft-status" class="hidden text-xs mt-1"></p>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="bg-primary hover:bg-primary/90 text-white font-medium rounded-lg px-5 py-2.5 text-sm">
                    Dokumentum kiállítása (PDF)
                </button>
                <a href="{{ route('patients.index') }}"
                   class="border border-slate-300 rounded-lg px-5 py-2.5 text-sm font-medium hover:bg-slate-50">
                    Mégsem
                </a>
            </div>
        </form>
    </div>

    <script>
        (function () {
            var typeSelect = document.getElementById('document-type');
            var patientSelect = document.getElementById('document-patient');
            var hint = document.getElementById('patient-hint');
            var sickLeaveBox = document.getElementById('sick-leave-fields');
            var sickFrom = document.getElementById('sick-leave-from');
            var sickTo = document.getElementById('sick-leave-to');
            var durationLabel = document.getElementById('sick-leave-duration');

            function sync() {
                var option = typeSelect.selectedOptions[0];
                var requiresDiagnosis = option.dataset.requiresDiagnosis === '1';
                var requiresPeriod = option.dataset.requiresPeriod === '1';
                var blockedSelected = false;

                Array.prototype.forEach.call(patientSelect.options, function (opt) {
                    if (!opt.value) return;
                    var blocked = requiresDiagnosis && opt.dataset.diagnosed !== '1';
                    opt.disabled = blocked;
                    if (blocked && opt.selected) blockedSelected = true;
                });

                if (blockedSelected) patientSelect.value = '';
                hint.classList.toggle('hidden', !requiresDiagnosis);
                sickLeaveBox.classList.toggle('hidden', !requiresPeriod);
                [sickFrom, sickTo, sickLeaveBox.querySelector('select')].forEach(function (el) {
                    el.disabled = !requiresPeriod
                });
                updateDuration();
            }

            function updateDuration() {
                if (sickLeaveBox.classList.contains('hidden') || !sickFrom.value || !sickTo.value) {
                    durationLabel.textContent = '';
                    return;
                }
                var from = new Date(sickFrom.value);
                var to = new Date(sickTo.value);
                var days = Math.round((to - from) / 86400000) + 1;

                if (days < 1) {
                    durationLabel.textContent = 'A záró dátum nem lehet korábbi a kezdő dátumnál.';
                    durationLabel.className = 'text-xs text-red-500';
                } else {
                    durationLabel.textContent = 'Az igazolt időtartam ' + days + ' nap.';
                    durationLabel.className = 'text-xs text-slate-500';
                }
            }

            typeSelect.addEventListener('change', sync);
            sickFrom.addEventListener('change', updateDuration);
            sickTo.addEventListener('change', updateDuration);
            sync();

            var draftBtn = document.getElementById('ai-draft-btn');
            var noteField = document.getElementById('document-note');
            var aiFlag = document.getElementById('document-ai-drafted');
            var draftStatus = document.getElementById('ai-draft-status');
            var csrfToken = document.querySelector('#document-form input[name="_token"]').value;
            var draftBtnLabel = draftBtn.textContent;

            function setDraftStatus(text, cls) {
                draftStatus.textContent = text;
                draftStatus.className = 'text-xs mt-1 ' + cls;
            }

            draftBtn.addEventListener('click', function () {
                if (!patientSelect.value) {
                    setDraftStatus('Előbb válasszon pácienst.', 'text-red-500');
                    patientSelect.focus();
                    return;
                }
                if (noteField.value.trim() !== ''
                    && !confirm('A meglévő szöveget lecseréli az AI javaslata. Folytatja?')) {
                    return;
                }

                draftBtn.disabled = true;
                draftBtn.textContent = 'Szöveg készül...';
                setDraftStatus('Az AI a páciens adatai alapján fogalmazza a szöveget...', 'text-slate-500');

                fetch(@json(route('documents.draft')), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        patient_id: patientSelect.value,
                        type: typeSelect.value,
                        hint: document.getElementById('ai-draft-hint').value
                    })
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
                            var firstError = r.data.errors ? Object.values(r.data.errors)[0][0] : null;
                            throw new Error(firstError || r.data.message || 'Ismeretlen hiba történt.');
                        }
                        noteField.value = r.data.text;
                        aiFlag.value = '1';
                        setDraftStatus('AI által javasolt szöveg. Kiállítás előtt olvassa át, javítsa, és töltse ki a [ ] helykitöltőket.', 'text-violet-600');
                    })
                    .catch(function (err) {
                        setDraftStatus(err.message, 'text-red-500');
                    })
                    .finally(function () {
                        draftBtn.disabled = false;
                        draftBtn.textContent = draftBtnLabel;
                    });
            });

            document.getElementById('document-form').addEventListener('submit', function (e) {
                if (/\[[^\]]+\]/.test(noteField.value)
                    && !confirm('A szövegben kitöltetlen [helykitöltő] maradt. Biztosan kiállítja így?')) {
                    e.preventDefault();
                }
            });
        })();
    </script>
@endsection
