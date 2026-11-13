<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'BugHunt VR') }}</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|chakra-petch:500,600,700|jetbrains-mono:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-chamber-950 text-gray-700">
        <div class="hud-floor" aria-hidden="true"></div>
        <div class="relative z-10 min-h-screen flex flex-col">
            @include('layouts.navigation')

            <!-- Main Content Container -->
            <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10">
                {{ $slot }}
            </main>

            <!-- Friendly Footer -->
            <footer class="mt-auto border-t border-indigo-500/20 bg-chamber-950/70 py-6 text-center font-mono text-[11px] tracking-wide text-gray-400">
                <p>&copy; {{ date('Y') }} BugHunt VR &bull; Educational VR Escape Room for Software Testing</p>
            </footer>
        </div>
    </body>
</html>
