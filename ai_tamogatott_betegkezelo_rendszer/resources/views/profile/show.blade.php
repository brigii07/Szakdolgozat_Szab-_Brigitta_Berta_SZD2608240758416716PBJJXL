@extends('layouts.app')

@section('title', 'Profilom - MedCare Rendszer')
@section('page-title', 'Profilom')

@section('content')
    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-6 flex items-center gap-4 max-w-2xl mx-auto">
        <div class="h-16 w-16 rounded-full bg-primary/10 text-primary flex items-center justify-center text-2xl font-semibold shrink-0">
            {{ strtoupper(substr($user->name, 0, 1)) }}
        </div>
        <div>
            <h2 class="text-xl font-semibold">{{ $user->name }}</h2>
            <p class="text-sm text-slate-500">{{ $user->email }}</p>
            <span class="inline-block mt-1 px-2.5 py-1 rounded-full text-xs font-medium bg-primary/10 text-primary">
                {{ $user->role === 'doctor' ? 'Orvos' : 'Asszisztens' }}
            </span>
            @if ($user->role === 'assistant' && $user->doctor)
                <span class="text-xs text-slate-400 ml-2">Felügyelő orvos: {{ $user->doctor->name }}</span>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 max-w-2xl mx-auto">
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="font-semibold mb-4">Adatok szerkesztése</h3>
            <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-sm font-medium mb-1">Név</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">E-mail cím</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <button type="submit" class="bg-primary hover:bg-primary/90 text-white font-medium rounded-lg px-5 py-2.5 text-sm">
                    Mentés
                </button>
            </form>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="font-semibold mb-4">Jelszó megváltoztatása</h3>
            <form method="POST" action="{{ route('profile.password') }}" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-sm font-medium mb-1">Jelenlegi jelszó</label>
                    <input type="password" name="current_password" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Új jelszó</label>
                    <input type="password" name="new_password" required minlength="8"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Új jelszó megerősítése</label>
                    <input type="password" name="new_password_confirmation" required minlength="8"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <button type="submit" class="bg-primary hover:bg-primary/90 text-white font-medium rounded-lg px-5 py-2.5 text-sm">
                    Jelszó módosítása
                </button>
            </form>
        </div>
    </div>
@endsection
