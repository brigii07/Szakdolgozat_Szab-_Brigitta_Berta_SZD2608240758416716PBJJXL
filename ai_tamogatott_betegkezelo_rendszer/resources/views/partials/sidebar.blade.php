@php
    $navItem = function (string $routeName, string $label, string $iconPath) {
        $active = request()->routeIs($routeName . '*');
        return compact('active', 'label', 'iconPath', 'routeName');
    };
@endphp
<aside class="w-64 shrink-0 bg-sidebar text-slate-300 flex flex-col">
    <div class="flex items-center gap-2 px-5 py-5 text-white font-semibold text-lg">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-primary" viewBox="0 0 24 24" fill="currentColor">
            <path
                d="M12 21s-6.5-4.35-9.33-8.06C.89 10.6 1 7.6 3.2 5.6a5 5 0 0 1 7-.3L12 7l1.8-1.7a5 5 0 0 1 7 .3c2.2 2 2.31 5 .53 7.34C18.5 16.65 12 21 12 21z"/>
        </svg>
        MedCare
    </div>

    <nav class="flex-1 px-3 py-2 space-y-1 text-sm">
        <a href="{{ route('dashboard') }}"
           class="flex items-center gap-3 rounded-lg px-3 py-2.5 {{ request()->routeIs('dashboard') ? 'bg-primary text-white' : 'hover:bg-sidebarhover' }}">
            <span>🏠</span> Főoldal
        </a>
        <a href="{{ route('patients.index') }}"
           class="flex items-center gap-3 rounded-lg px-3 py-2.5 {{ request()->routeIs('patients.index') || request()->routeIs('patients.show') ? 'bg-primary text-white' : 'hover:bg-sidebarhover' }}">
            <span>📋</span> Rögzített betegek
        </a>
        <a href="{{ route('patients.create') }}"
           class="flex items-center gap-3 rounded-lg px-3 py-2.5 {{ request()->routeIs('patients.create') ? 'bg-primary text-white' : 'hover:bg-sidebarhover' }}">
            <span>➕</span> Páciens felvétele
        </a>

        @can('issue-documents')
            <a href="{{ route('documents.create') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2.5 {{ request()->routeIs('documents.*') ? 'bg-primary text-white' : 'hover:bg-sidebarhover' }}">
                <span>📄</span> Dokumentumok
            </a>
        @endcan

        @can('use-ai-assistant')
            <a href="{{ route('patients.index', ['status' => 'WAITING_AI_APPROVAL']) }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2.5 hover:bg-sidebarhover">
                <span>🤖</span> AI asszisztens
            </a>
        @endcan
    </nav>

    <form method="POST" action="{{ route('logout') }}" class="px-3 py-4 border-t border-white/10">
        @csrf
        <button type="submit"
                class="w-full text-left flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm hover:bg-sidebarhover">
            <span>🚪</span> Kijelentkezés
        </button>
    </form>
</aside>
