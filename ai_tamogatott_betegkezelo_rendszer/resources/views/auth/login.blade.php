<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bejelentkezés - MedCare Rendszer</title>
    <link rel="icon" type="image/svg+xml"
          href="data:image/svg+xml,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20viewBox='0%200%2024%2024'%3E%3Crect%20width='24'%20height='24'%20rx='6'%20fill='%230F172A'/%3E%3Cpath%20fill='%232563EB'%20d='M12%2021s-6.5-4.35-9.33-8.06C.89%2010.6%201%207.6%203.2%205.6a5%205%200%200%201%207-.3L12%207l1.8-1.7a5%205%200%200%201%207%20.3c2.2%202%202.31%205%20.53%207.34C18.5%2016.65%2012%2021%2012%2021z'/%3E%3C/svg%3E">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {primary: '#2563EB', secondary: '#10B981', textmain: '#1F2937'},
                    fontFamily: {sans: ['Inter', 'ui-sans-serif', 'system-ui']},
                },
            },
        }
    </script>
</head>
<body class="font-sans text-textmain">
<div class="min-h-screen grid grid-cols-1 md:grid-cols-2">
    <div class="hidden md:block bg-slate-800 bg-gradient-to-br from-slate-700 to-slate-900"></div>

    <div class="flex items-center justify-center px-6 py-12">
        <div class="w-full max-w-sm">
            <div class="flex flex-col items-center mb-8">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-9 w-9 text-primary mb-2" viewBox="0 0 24 24"
                     fill="currentColor">
                    <path
                        d="M12 21s-6.5-4.35-9.33-8.06C.89 10.6 1 7.6 3.2 5.6a5 5 0 0 1 7-.3L12 7l1.8-1.7a5 5 0 0 1 7 .3c2.2 2 2.31 5 .53 7.34C18.5 16.65 12 21 12 21z"/>
                </svg>
                <h1 class="text-xl font-semibold">MedCare Rendszer</h1>
                <p class="text-sm text-slate-400">Orvosi betegellátó rendszer</p>
            </div>

            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-600 px-4 py-3 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.attempt') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium mb-1" for="email">E-mail cím</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required
                           placeholder="orvos@korhaz.hu"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1" for="password">Jelszó</label>
                    <input type="password" name="password" id="password" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <button type="submit"
                        class="w-full bg-primary hover:bg-primary/90 text-white font-medium rounded-lg py-2.5 text-sm transition">
                    Bejelentkezés
                </button>

                <div class="text-center">
                    <a href="#" id="forgot-password-link" class="text-sm text-primary hover:underline">Elfelejtette
                        jelszavát?</a>
                    <p id="forgot-password-notice" class="hidden text-xs text-slate-400 mt-2">
                        Ez a funkció még nem elérhető.
                    </p>
                </div>
            </form>

            <script>
                document.getElementById('forgot-password-link').addEventListener('click', function (e) {
                    e.preventDefault();
                    var notice = document.getElementById('forgot-password-notice');
                    notice.classList.remove('hidden');
                    setTimeout(function () {
                        notice.classList.add('hidden');
                    }, 3000);
                });
            </script>
        </div>
    </div>
</div>
</body>
</html>
