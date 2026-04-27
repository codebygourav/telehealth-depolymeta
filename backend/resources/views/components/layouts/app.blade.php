<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Vendor Registration' }}</title>
    @vite('resources/css/app.css')
    @livewireStyles
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <main class="w-full">
        {{ $slot }}
    </main>
    @livewireScripts
</body>

</html>
