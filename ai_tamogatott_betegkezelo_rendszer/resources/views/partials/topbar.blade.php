<header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6">
    <h1 class="text-lg font-semibold text-textmain">@yield('page-title', '')</h1>

    <div class="flex items-center gap-4">
        <div class="relative">
            <button type="button" id="bell-button" class="relative text-slate-500 hover:text-textmain">
                <span>🔔</span>
                @if ($urgentCount > 0)
                    <span
                        class="absolute -top-1.5 -right-2 min-w-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-semibold flex items-center justify-center">{{ $urgentCount }} </span>
                @endif
            </button>
            <div id="bell-notice"
                 class="hidden absolute right-0 top-full mt-2 w-64 bg-white border border-slate-200 rounded-lg shadow-lg px-3 py-2.5 text-xs text-slate-500 z-10">
                @if ($urgentCount > 0)
                    <a href="{{ route('dashboard') }}" class="text-red-600 font-medium">{{ $urgentCount }} sürgős
                        páciens vár ellátásra</a>
                @else
                    Nincs sürgős páciens.
                @endif
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('profile.show') }}" class="flex items-center gap-2 hover:opacity-80" title="Profilom">
                <div
                    class="h-9 w-9 rounded-full bg-primary/10 text-primary flex items-center justify-center font-semibold">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="text-sm">
                    <div class="font-medium text-textmain">{{ auth()->user()->name }}</div>
                    <div
                        class="text-slate-400 text-xs">{{ auth()->user()->role === 'doctor' ? 'Orvos' : 'Asszisztens' }}</div>
                </div>
            </a>

            <button type="button" id="theme-toggle" title="Sötét / világos nézet"
                    class="ml-1 h-8 w-8 rounded-full flex items-center justify-center text-slate-400 hover:text-primary hover:bg-slate-100">
                <svg id="theme-icon-moon" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                </svg>
                <svg id="theme-icon-sun" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 hidden" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="4"/>
                    <path
                        d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>
                </svg>
            </button>
        </div>
    </div>
</header>

<script>
    (function () {
        var toggle = document.getElementById('theme-toggle');
        var moonIcon = document.getElementById('theme-icon-moon');
        var sunIcon = document.getElementById('theme-icon-sun');

        function syncIcons() {
            var isDark = document.documentElement.classList.contains('dark');
            moonIcon.classList.toggle('hidden', isDark);
            sunIcon.classList.toggle('hidden', !isDark);
        }

        syncIcons();

        toggle.addEventListener('click', function () {
            document.documentElement.classList.toggle('dark');
            var isDark = document.documentElement.classList.contains('dark');
            localStorage.setItem('medcare-theme', isDark ? 'dark' : 'light');
            syncIcons();
        });
    })();

    (function () {
        var bellButton = document.getElementById('bell-button');
        var bellNotice = document.getElementById('bell-notice');

        bellButton.addEventListener('click', function (e) {
            e.stopPropagation();
            bellNotice.classList.toggle('hidden');
        });

        document.addEventListener('click', function (e) {
            if (!bellNotice.contains(e.target) && e.target !== bellButton) {
                bellNotice.classList.add('hidden');
            }
        });
    })();
</script>
