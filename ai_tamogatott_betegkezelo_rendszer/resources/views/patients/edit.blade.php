@extends('layouts.app')

@section('title', 'Páciens szerkesztése - MedCare Rendszer')
@section('page-title', 'Páciens szerkesztése')

@section('content')
    <div class="bg-white rounded-xl border border-slate-200 p-6 max-w-2xl mx-auto">
        <h2 class="font-semibold mb-1">Személyes adatok szerkesztése</h2>
        <p class="text-sm text-slate-400 mb-6">{{ $patient->name }} adatainak módosítása.</p>

        <form method="POST" action="{{ route('patients.update', $patient) }}" class="space-y-4">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium mb-1">Név</label>
                    <input type="text" name="name" value="{{ old('name', $patient->name) }}" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Születési idő</label>
                    <input type="text" name="birth_date" value="{{ old('birth_date', $patient->birth_date) }}" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Születési hely</label>
                    <input type="text" name="birth_place" value="{{ old('birth_place', $patient->birth_place) }}"
                           required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Nem</label>
                    <select name="gender" required
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                        <option value="M" @selected($patient->gender === 'M')>Férfi</option>
                        <option value="F" @selected($patient->gender === 'F')>Nő</option>
                        <option value="Other" @selected($patient->gender === 'Other')>Egyéb</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">TAJ-szám</label>
                    <input type="number" name="taj_number" value="{{ old('taj_number', $patient->taj_number) }}"
                           required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Telefon</label>
                    <input type="text" name="phone" value="{{ old('phone', $patient->phone) }}" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">E-mail</label>
                    <input type="email" name="email" value="{{ old('email', $patient->email) }}" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium mb-1">Lakcím</label>
                    <input type="text" name="address" value="{{ old('address', $patient->address) }}" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
            </div>

            <div class="flex gap-3 pt-4">
                <button type="submit"
                        class="bg-primary hover:bg-primary/90 text-white font-medium rounded-lg px-5 py-2.5 text-sm">
                    Mentés
                </button>
                <a href="{{ route('patients.show', $patient) }}"
                   class="border border-slate-300 rounded-lg px-5 py-2.5 text-sm font-medium hover:bg-slate-50">
                    Mégsem
                </a>
            </div>
        </form>
    </div>
@endsection
