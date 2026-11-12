@php use App\Models\Patient; @endphp
@extends('layouts.app')

@section('title', 'Betegek listája - MedCare Rendszer')
@section('page-title', 'Betegek listája')

@section('content')
    <div class="bg-white rounded-xl border border-slate-200">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 border-b border-slate-200">
            <form method="GET" class="flex items-center gap-2 flex-1">
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="Keresés név vagy TAJ szám alapján..."
                       class="w-full max-w-xs rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Státusz: Összes</option>
                    @foreach ([Patient::STATUS_NO_INFORMATION, Patient::STATUS_WAITING_FOR_DIAGNOSIS, Patient::STATUS_WAITING_AI_APPROVAL, Patient::STATUS_DIAGNOSED] as $status)
                        <option
                            value="{{ $status }}" @selected(request('status') === $status)>{{ Patient::statusLabel($status) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="text-sm text-primary font-medium">Szűrés</button>
            </form>
        </div>

        <table class="w-full text-sm">
            <thead>
            <tr class="text-left text-slate-400 border-b border-slate-200">
                <th class="px-4 py-3 font-medium">Név</th>
                <th class="px-4 py-3 font-medium">Születési idő</th>
                <th class="px-4 py-3 font-medium">TAJ-szám</th>
                <th class="px-4 py-3 font-medium">Státusz</th>
                <th class="px-4 py-3 font-medium">Műveletek</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($patients as $patient)
                <tr class="border-b border-slate-100 hover:bg-slate-50">
                    <td class="px-4 py-3 font-medium">{{ $patient->name }} @if ($patient->is_urgent)
                            <span
                                class="ml-2 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-red-100 text-red-600">SÜRGŐS</span>
                        @endif </td>
                    <td class="px-4 py-3 text-slate-500">{{ $patient->birth_date }}</td>
                    <td class="px-4 py-3 text-slate-500">{{ $patient->taj_number }}</td>
                    <td class="px-4 py-3">
                            <span
                                class="px-2.5 py-1 rounded-full text-xs font-medium {{ Patient::statusBadgeClass($patient->status)}}">
                                {{ Patient::statusLabel($patient->status) }}
                            </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3 text-slate-400">
                            <a href="{{ route('patients.show', $patient) }}" title="Megtekintés"
                               class="hover:text-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                     stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </a>
                            <a href="{{ route('patients.edit', $patient) }}" title="Szerkesztés"
                               class="hover:text-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                     stroke-linejoin="round">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                            </a>
                            @can('manage-diagnosis')
                                <form method="POST" action="{{ route('patients.destroy', $patient) }}"
                                      onsubmit="return confirm('Biztosan törlöd {{ $patient->name }} adatait? Ez minden kapcsolódó adatot (tünetek, vérképek, diagnózisok, dokumentumok) is töröl.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Törlés" class="hover:text-red-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24"
                                             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                             stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"/>
                                            <path
                                                d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                        </svg>
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-10 text-center text-slate-400">Nincs a szűrésnek megfelelő páciens.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>

        <div class="p-4 border-t border-slate-200">
            {{ $patients->links() }}
        </div>
    </div>
@endsection
