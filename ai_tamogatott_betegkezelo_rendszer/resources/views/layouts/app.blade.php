<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'MedCare Rendszer')</title>
    <link rel="icon" type="image/svg+xml"
          href="data:image/svg+xml,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20viewBox='0%200%2024%2024'%3E%3Crect%20width='24'%20height='24'%20rx='6'%20fill='%230F172A'/%3E%3Cpath%20fill='%232563EB'%20d='M12%2021s-6.5-4.35-9.33-8.06C.89%2010.6%201%207.6%203.2%205.6a5%205%200%200%201%207-.3L12%207l1.8-1.7a5%205%200%200%201%207%20.3c2.2%202%202.31%205%20.53%207.34C18.5%2016.65%2012%2021%2012%2021z'/%3E%3C/svg%3E">

    <script>
        (function () {
            var saved = localStorage.getItem('medcare-theme');
            var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (saved === 'dark' || (!saved && prefersDark)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#2563EB',
                        secondary: '#10B981',
                        bgapp: '#F5F7FA',
                        textmain: '#1F2937',
                        sidebar: '#0F172A',
                        sidebarhover: '#1E293B',
                    },
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui'],
                    },
                },
            },
        }
    </script>

    <style>
        html.dark body {
            background-color: #0F172A;
            color: #E2E8F0;
        }

        html.dark .bg-bgapp {
            background-color: #0F172A;
        }

        html.dark .bg-white {
            background-color: #1E293B;
        }

        html.dark .text-textmain {
            color: #E5E7EB;
        }

        html.dark .text-slate-400 {
            color: #94A3B8;
        }

        html.dark .text-slate-500 {
            color: #94A3B8;
        }

        html.dark .text-slate-600 {
            color: #CBD5E1;
        }

        html.dark .border-slate-200 {
            border-color: #334155;
        }

        html.dark .border-slate-100 {
            border-color: #334155;
        }

        html.dark .border-slate-300 {
            border-color: #475569;
        }

        html.dark .hover\:bg-slate-50:hover {
            background-color: #273449;
        }

        html.dark .bg-slate-50 {
            background-color: #1A2536;
        }

        html.dark .bg-slate-100 {
            background-color: #273449;
            color: #CBD5E1;
        }

        html.dark input, html.dark select, html.dark textarea {
            background-color: #0F172A;
            border-color: #475569;
            color: #E5E7EB;
        }

        html.dark input::placeholder, html.dark textarea::placeholder {
            color: #64748B;
        }

        html.dark .bg-sidebar {
            background-color: #000000;
        }

        html.dark aside.bg-sidebar {
            border-right: 1px solid #1E293B;
        }

        html.dark table thead tr {
            color: #94A3B8;
        }

        html.dark tbody tr:hover {
            background-color: #1A2536;
        }

        html.dark .bg-red-50 {
            background-color: rgba(239, 68, 68, 0.12);
        }

        html.dark .border-red-200 {
            border-color: rgba(239, 68, 68, 0.35);
        }

        html.dark .bg-violet-50 {
            background-color: rgba(139, 92, 246, 0.12);
        }

        html.dark .bg-amber-50 {
            background-color: rgba(245, 158, 11, 0.12);
        }
    </style>

    @stack('styles')
</head>
<body class="bg-bgapp text-textmain font-sans antialiased">
<div class="flex min-h-screen">
    @include('partials.sidebar')

    <div class="flex-1 flex flex-col min-w-0">
        @include('partials.topbar')

        <main class="flex-1 p-6">
            @if (session('success'))
                <div
                    class="mb-4 rounded-lg bg-secondary/10 border border-secondary/30 text-secondary px-4 py-3 text-sm font-medium">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-600 px-4 py-3 text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
