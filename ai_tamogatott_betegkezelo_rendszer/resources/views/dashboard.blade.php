@php use App\Models\Patient; @endphp
@extends('layouts.app')

@section('title', 'Főoldal - MedCare Rendszer')
@section('page-title', 'Főoldal / Dashboard')

@php
    $queues = [
        [
            'title' => 'Diagnosztizálásra vár',
            'data' => $waitingForDiagnosis,
            'status' => Patient::STATUS_WAITING_FOR_DIAGNOSIS,
            'countClass' => 'text-primary',
            'empty' => 'Nincs diagnózisra váró páciens.',
        ],
        [
            'title' => 'Jóváhagyásra vár',
            'data' => $waitingAiApproval,
            'status' => Patient::STATUS_WAITING_AI_APPROVAL,
            'countClass' => 'text-violet-500',
            'empty' => 'Nincs jóváhagyásra váró AI javaslat.',
        ],
    ];
@endphp

@section('content')
    <p class="text-slate-500 mb-6">Üdvözöljük, {{ auth()->user()->name }}!</p>
    @if ($urgentPatients->isNotEmpty())
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-5">
            <h2 class="font-semibold text-red-700 mb-3">🚨 Sürgős páciensek ({{ $urgentPatients->count() }})</h2>
            <ul class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                @foreach ($urgentPatients as $p)
                    <li>
                        <a href="{{ route('patients.show', $p) }}"
                           class="block rounded-lg bg-white border border-red-200 px-4 py-3 hover:border-red-400">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-medium truncate">{{ $p->name }}</span>
                                <span
                                    class="px-2 py-0.5 rounded-full text-[11px] font-medium shrink-0 {{ Patient::statusBadgeClass($p->status) }}">
                                    {{ Patient::statusLabel($p->status) }}
                                </span>
                            </div>
                            <div class="text-xs text-slate-400 mt-1">
                                Felvéve: {{ $p->created_at->diffForHumans() }}
                                @if ($p->status === Patient::STATUS_NO_INFORMATION)
                                    · <span class="text-red-600 font-medium">Még nincs tünet rögzítve</span>
                                @endif
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        @foreach ($queues as $queue)
            <div class="bg-white rounded-xl border border-slate-200 flex flex-col">
                <div class="flex items-center justify-between p-5 border-b border-slate-200">
                    <div>
                        <div class="text-sm text-slate-500">{{ $queue['title'] }}</div>
                        <div
                            class="text-4xl font-semibold {{ $queue['countClass'] }}">{{ $queue['data']['count'] }}</div>
                    </div>
                    <a href="{{ route('patients.index', ['status' => $queue['status']]) }}"
                       class="text-sm text-primary font-medium">Összes megtekintése →</a>
                </div>

                <ul class="divide-y divide-slate-100 flex-1">
                    @forelse ($queue['data']['patients'] as $p)
                        @php
                            $since = $p->status_changed_at;
                            $isOld = $since && $since->lt(now()->subDays(3));
                        @endphp
                        <li>
                            <a href="{{ $queue['status'] === Patient::STATUS_WAITING_AI_APPROVAL && auth()->user()->isDoctor() ? route('ai.analyze', $p) : route('patients.show', $p) }}"
                               class="flex items-center justify-between gap-4 px-5 py-3 hover:bg-slate-50">
                                <div class="min-w-0">
                                    <div class="font-medium truncate flex items-center gap-2">
                                        {{ $p->name }}
                                        @if ($p->is_urgent)
                                            <span
                                                class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-red-100 text-red-600">SÜRGŐS</span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-slate-400">
                                        TAJ: {{ $p->taj_number }} · {{ $p->symptoms_count }} rögzített tünet
                                    </div>
                                </div>
                                <div
                                    class="text-xs shrink-0 {{ $isOld ? 'text-red-500 font-medium' : 'text-slate-400' }}">
                                    {{ $since ? $since->diffForHumans() : '–' }}
                                </div>
                            </a>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-sm text-slate-400">{{ $queue['empty'] }}</li>
                    @endforelse
                </ul>

                @if ($queue['data']['count'] > $queue['data']['patients']->count())
                    <div class="px-5 py-2 text-xs text-slate-400 border-t border-slate-100">
                        + további {{ $queue['data']['count'] - $queue['data']['patients']->count() }} páciens
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    @can('manage-diagnosis')
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <h2 class="font-semibold mb-1">Kóros laboreredmények</h2>
                <p class="text-xs text-slate-400 mb-4">Az elmúlt 14 nap referencián kívüli értékei</p>

                <ul class="space-y-3">
                    @forelse ($abnormalLabs as $lab)
                        <li class="border border-slate-100 rounded-lg p-3">
                            <div class="flex items-center justify-between mb-2">
                                <a href="{{ route('patients.show', $lab['patient']) }}"
                                   class="font-medium hover:text-primary">{{ $lab['patient']->name }}</a>
                                <span
                                    class="text-xs text-slate-400">{{ $lab['created_at']->format('Y.m.d. H:i') }}</span>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($lab['results'] as $r)
                                    @php $high = $r->deviationDirection() === 'MAGAS'; @endphp
                                    <span class="px-2 py-1 rounded-md text-xs bg-red-50 text-red-600">
                                        {{ $r->parameter_name }}: <strong>{{ $r->value }}</strong> {{ $r->unit }}
                                        {{ $high ? '↑' : '↓' }}
                                        <span
                                            class="text-                                            class="
                                            text-slate-400">({{ $r->referenceLabel() }})</span>
                                    -400">({{ $r->reference_min }}–{{ $r->reference_max }})</span>
                                    </span>
                                @endforeach
                            </div>
                        </li>
                    @empty
                        <li class="text-sm text-slate-400">Nincs kóros eredmény az elmúlt időszakban.</li>
                    @endforelse
                </ul>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <h2 class="font-semibold mb-1">Legutóbbi tevékenység</h2>
                <p class="text-xs text-slate-400 mb-4">Ön és asszisztensei által rögzített műveletek</p>

                <ul class="divide-y divide-slate-100">
                    @forelse ($recentActivity as $log)
                        <li class="py-2.5 flex items-start justify-between gap-4 text-sm">
                            <div class="min-w-0">
                                <div>
                                    <span class="font-medium">
                                        {{ $log->user_id === auth()->id() ? 'Ön' : $log->user?->name }}
                                    </span>
                                    <span class="text-slate-500">– {{ $log->action }}</span>
                                </div>
                                @if ($log->patient)
                                    <a href="{{ route('patients.show', $log->patient) }}"
                                       class="text-xs text-primary">{{ $log->patient->name }}</a>
                                @endif
                            </div>
                            <span class="text-xs text-slate-400 shrink-0">{{ $log->created_at->diffForHumans() }}</span>
                        </li>
                    @empty
                        <li class="py-4 text-sm text-slate-400">Még nincs rögzített tevékenység.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    @endcan
@endsection
