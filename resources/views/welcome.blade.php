<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'BugHunt VR') }} - Software Testing VR Escape Room</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-slate-50 text-gray-900 selection:bg-indigo-500 selection:text-white min-h-screen flex flex-col">
        <!-- Top Nav -->
        <nav class="bg-white border-b border-gray-200/80">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <div class="flex items-center">
                    <x-application-logo />
                </div>

                <div>
                    @auth
                        <a href="{{ route('dashboard') }}">
                            <x-primary-button type="button">
                                Go to Dashboard &rarr;
                            </x-primary-button>
                        </a>
                    @else
                        <a href="{{ route('login') }}">
                            <x-primary-button type="button">
                                Log In &rarr;
                            </x-primary-button>
                        </a>
                    @endauth
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <main class="flex-1 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-20 flex flex-col items-center text-center">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-indigo-50 border border-indigo-100 text-indigo-700 text-xs sm:text-sm font-semibold mb-6">
                <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                VR Escape Room &bull; Software Testing Edition
            </div>

            <h1 class="text-3xl sm:text-5xl font-black text-gray-900 tracking-tight max-w-3xl leading-tight sm:leading-tight">
                Hunt Down Software Bugs in Virtual Reality!
            </h1>

            <p class="text-base sm:text-lg text-gray-600 mt-4 max-w-2xl">
                Step into an interactive 3D escape room designed to teach core software testing concepts.
            </p>

            <div class="mt-8 flex flex-col sm:flex-row items-center gap-4">
                @auth
                    <a href="{{ route('dashboard') }}" class="w-full sm:w-auto">
                        <x-primary-button class="w-full sm:w-auto text-base px-8 py-3.5">
                            Open Your Dashboard
                        </x-primary-button>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="w-full sm:w-auto">
                        <x-primary-button class="w-full sm:w-auto text-base px-8 py-3.5">
                            Log In to Start
                        </x-primary-button>
                    </a>
                @endauth
            </div>

            <!-- Features Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-16 w-full text-left">
                <x-card class="border-t-4 border-t-indigo-600">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-lg mb-4">
                        1
                    </div>
                    <h2 class="text-base font-bold text-gray-900">Pick a Room</h2>
                    <p class="text-xs sm:text-sm text-gray-500 mt-2">
                        Choose your software testing challenge from the web dashboard.
                    </p>
                </x-card>

                <x-card class="border-t-4 border-t-blue-500">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-lg mb-4">
                        2
                    </div>
                    <h2 class="text-base font-bold text-gray-900">Enter 6-Digit Code</h2>
                    <p class="text-xs sm:text-sm text-gray-500 mt-2">
                        Get a 6-digit code on web, then punch it into the VR game keypad.
                    </p>
                </x-card>

                <x-card class="border-t-4 border-t-emerald-500">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-lg mb-4">
                        3
                    </div>
                    <h2 class="text-base font-bold text-gray-900">Escape & Learn</h2>
                    <p class="text-xs sm:text-sm text-gray-500 mt-2">
                        Solve bugs to open escape doors and receive real-time test reports.
                    </p>
                </x-card>
            </div>
        </main>

        <footer class="border-t border-gray-200/80 bg-white/60 py-6 text-center text-xs text-gray-500">
            <p>&copy; {{ date('Y') }} BugHunt VR &bull; Graduation Project System</p>
        </footer>
    </body>
</html>
