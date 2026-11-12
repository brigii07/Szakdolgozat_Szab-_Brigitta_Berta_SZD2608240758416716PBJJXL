@extends('layouts.app')

@section('title', 'Páciens felvétele - MedCare Rendszer')
@section('page-title', 'Páciens felvétele')

@section('content')
    <div class="bg-white rounded-xl border border-slate-200 p-6 max-w-2xl mx-auto">
        <h2 class="font-semibold mb-1">Személyes adatok</h2>
        <p class="text-sm text-slate-400 mb-6">Az új páciens alapadatainak rögzítése.</p>

        <form method="POST" action="{{ route('patients.store') }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium mb-1">Név</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Születési idő</label>
                    <input type="text" name="birth_date" value="{{ old('birth_date') }}" placeholder="ÉÉÉÉ.HH.NN."
                           required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Születési hely</label>
                    <input type="text" name="birth_place" value="{{ old('birth_place') }}" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Nem</label>
                    <select name="gender" required
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                        <option value="M">Férfi</option>
                        <option value="F">Nő</option>
                        <option value="Other">Egyéb</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">TAJ-szám</label>
                    <input type="number" name="taj_number" value="{{ old('taj_number') }}" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Telefon</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">E-mail</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium mb-1">Lakcím</label>
                    <input type="text" name="address" value="{{ old('address') }}" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="is_urgent" value="0">
                    <input type="checkbox" name="is_urgent" value="1"
                           @checked(old('is_urgent', $patient->is_urgent ?? false))
                           class="rounded border-slate-300 text-red-600">
                    Sürgős eset
                </label>

            </div>

            <div class="flex gap-3 pt-4">
                <button type="submit"
                        class="bg-primary hover:bg-primary/90 text-white font-medium rounded-lg px-5 py-2.5 text-sm">
                    Mentés
                </button>
                <a href="{{ route('patients.index') }}"
                   class="border border-slate-300 rounded-lg px-5 py-2.5 text-sm font-medium hover:bg-slate-50">
                    Mégsem
                </a>
            </div>
        </form>
    </div>
@endsection
