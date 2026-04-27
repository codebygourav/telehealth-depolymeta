<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 - Page Not Found | {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Outfit:wght@700;800&display=swap');

        body {
            font-family: 'Inter', sans-serif;
        }

        .font-display {
            font-family: 'Outfit', sans-serif;
        }
    </style>
</head>

<body class="h-full bg-white flex items-center justify-center p-6 bg-slate-50">
    <div class="text-center max-w-xl">
        <h1 class="font-display font-black text-[10rem] leading-none text-slate-100 select-none">404</h1>
        <div class="relative px-8 py-10 bg-white rounded-3xl shadow-xl shadow-slate-200/50">
            <h2 class="text-3xl font-bold text-slate-900 mb-4">Page Missing</h2>
            <p class="text-slate-500 text-lg mb-8">We can't find the page you're looking for.</p>
            <a href="/" class="px-8 py-3 bg-slate-900 text-white rounded-2xl font-bold hover:bg-slate-800 transition-colors">
                Back to Home
            </a>
        </div>
    </div>
</body>

</html>