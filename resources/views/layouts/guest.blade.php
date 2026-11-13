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

        <div class="relative z-10 min-h-screen flex flex-col overflow-hidden"
             x-data="{
                secs: 3599, bugs: { a: true, b: true, c: true }, email: '', pw: '', tried: false,
                authFail: {{ $errors->any() ? 'true' : 'false' }},
                get caught() { return 3 - Object.values(this.bugs).filter(Boolean).length; },
                get clock() { return String(Math.floor(this.secs / 60)).padStart(2, '0') + ':' + String(this.secs % 60).padStart(2, '0'); },
                get emailOk() { return this.email.trim().length > 0; },
                get formatOk() { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.email.trim()); },
                get pwOk() { return this.pw.length > 0; },
                st(ok, fail) { return ok ? 'PASS' : (fail ? 'FAIL' : 'WAIT'); },
                get tests() {
                    return [
                        { n: 'email is not empty', s: this.st(this.emailOk, this.tried) },
                        { n: 'email has a valid format', s: this.st(this.formatOk, this.tried) },
                        { n: 'password is not empty', s: this.st(this.pwOk, this.tried) },
                        { n: 'credentials match a tester', s: this.authFail ? 'FAIL' : 'WAIT' },
                    ];
                },
                get passCount() { return this.tests.filter(t => t.s === 'PASS').length; },
                get failCount() { return this.tests.filter(t => t.s === 'FAIL').length; },
                init() {
                    setInterval(() => { if (this.secs > 0) this.secs--; }, 1000);
                    const e = document.getElementById('email'), p = document.getElementById('password'), f = e ? e.form : null;
                    if (e) { this.email = e.value; e.addEventListener('input', () => { this.email = e.value; this.authFail = false; }); }
                    if (p) { this.pw = p.value; p.addEventListener('input', () => { this.pw = p.value; this.authFail = false; }); }
                    if (f) f.addEventListener('submit', () => { this.tried = true; });
                }
             }">
            <!-- HUD strip -->
            <header class="flex flex-wrap items-center justify-between gap-3 px-5 sm:px-8 py-4 border-b border-gray-200 bg-chamber-950/80">
                <a href="/" class="inline-flex"><x-application-logo /></a>
                <div class="flex items-center gap-5 font-mono text-[12px] text-gray-500">
                    <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full animate-pulse" :class="authFail ? 'bg-rose-500 shadow-[0_0_10px_#ff5d5d]' : 'bg-amber-500 shadow-[0_0_10px_#ff8a3d]'"></span><span x-text="authFail ? 'INTRUSION ALERT' : 'AWAITING TESTER'">AWAITING TESTER</span></span>
                    <span class="hidden md:inline">ROOM <span class="text-gray-900">00 &middot; LOGIN VAULT</span></span>
                    <span class="hidden sm:inline">BUGS CAUGHT <span class="text-amber-500 font-semibold" x-text="caught + '/3'">0/3</span></span>
                    <span role="timer" aria-label="Mission clock" class="px-3 py-1.5 border border-amber-500 rounded-md text-amber-500 font-semibold text-base tracking-[.08em] bg-amber-50" x-text="clock">59:59</span>
                </div>
            </header>

            <div class="flex-1 flex flex-wrap items-center justify-center gap-7 px-4 sm:px-8 py-10 max-w-6xl w-full mx-auto">
                <!-- Mission brief (desktop only) -->
                <aside class="hidden lg:flex flex-col gap-5 flex-1 max-w-xs self-stretch hud-panel p-6">
                    <p class="hud-eyebrow">// mission brief</p>
                    <h2 class="font-display text-3xl font-bold text-gray-900 uppercase leading-[1.05]">
                        Find the bug.<br><span class="text-indigo-500">Escape the room.</span>
                    </h2>
                    <p class="text-sm text-gray-600 leading-relaxed">
                        You're locked in a lab full of broken code. Every puzzle is a real testing problem. Pass them all before the clock hits zero.
                    </p>
                    <div class="font-mono text-[13px] leading-8 text-gray-500 p-3.5 rounded-lg bg-chamber-950 border border-gray-100">
                        <div><span class="text-indigo-500">&gt;</span> boot lab_environment &hellip; <span class="text-emerald-500">ok</span></div>
                        <div><span class="text-indigo-500">&gt;</span> load puzzles &hellip; <span class="text-emerald-500">ok</span></div>
                        <div><span class="text-indigo-500">&gt;</span> door.state = <span class="text-amber-500">LOCKED</span></div>
                        <div><span class="text-indigo-500">&gt;</span> awaiting tester credentials <span class="hud-cursor"></span></div>
                    </div>
                    <div class="mt-auto space-y-2.5 text-sm text-gray-700">
                        <p class="font-mono text-[12px] tracking-[.14em] text-gray-500">ESCAPE ROUTE</p>
                        <p><span class="font-mono text-amber-500">01</span>&nbsp; Unlock this door and press Start room</p>
                        <p><span class="font-mono text-amber-500">02</span>&nbsp; Enter the 6-digit code inside the headset</p>
                        <p><span class="font-mono text-amber-500">03</span>&nbsp; Return here for your score and AI debrief</p>
                    </div>
                </aside>

                <!-- The door -->
                <main class="flex-[2_1_420px] w-full max-w-lg">
                    <div class="relative rounded-2xl border-2 border-gray-300 bg-chamber-800 overflow-hidden shadow-[0_0_0_6px_#070c11,0_0_60px_#3fe0c529,inset_0_0_40px_#00000099]">
                        <div class="hud-hazard h-3.5" aria-hidden="true"></div>

                        <div class="px-6 sm:px-10 pt-7 pb-9">
                            @php
                                $authHeading = match (true) {
                                    request()->routeIs('login') => ['// authenticate', 'Welcome back'],
                                    request()->routeIs('register') => ['// new player', 'Create your account'],
                                    request()->routeIs('password.request') => ['// recovery', 'Reset access'],
                                    request()->routeIs('password.reset') => ['// recovery', 'Choose a new password'],
                                    request()->routeIs('verification.notice') => ['// verification', 'Verify your email'],
                                    request()->routeIs('password.confirm') => ['// security check', 'Confirm password'],
                                    default => null,
                                };
                            @endphp
                            @if ($authHeading)
                                <div class="flex items-center gap-4 mb-6">
                                    <span class="shrink-0 w-14 h-14 rounded-2xl border-2 border-amber-500 grid place-items-center shadow-[0_0_22px_#ff8a3d59]" aria-hidden="true">
                                        <svg class="w-6 h-6 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 018 0v4"/></svg>
                                    </span>
                                    <div>
                                        <p class="hud-eyebrow !text-amber-500">{{ $authHeading[0] }}</p>
                                        <h1 class="font-display text-2xl sm:text-3xl font-bold text-gray-900 uppercase leading-tight">{{ $authHeading[1] }}</h1>
                                    </div>
                                </div>
                            @endif

                            {{ $slot }}
                        </div>

                        <div class="hud-hazard h-2 opacity-60" aria-hidden="true"></div>
                    </div>

                    <p class="mt-6 text-center font-mono text-[11px] tracking-wide text-gray-400">
                        BugHunt VR &bull; Software Testing VR Escape Room
                    </p>
                </main>

                <!-- Test runner (visual mirror of the form; never blocks submit) -->
                @if (request()->routeIs('login'))
                <section aria-label="Live test results" aria-live="polite" class="hidden lg:flex flex-col gap-3.5 flex-1 max-w-xs self-stretch hud-panel p-6 font-mono">
                    <div class="flex items-center justify-between">
                        <p class="hud-eyebrow">// test runner</p>
                        <span class="text-xs text-gray-500">login.spec</span>
                    </div>
                    <template x-for="t in tests" :key="t.n">
                        <div class="flex items-start gap-2.5 p-3 rounded-lg bg-chamber-950 border"
                             :class="t.s === 'PASS' ? 'border-emerald-500/30' : (t.s === 'FAIL' ? 'border-rose-500/30' : 'border-gray-100')">
                            <span class="shrink-0 min-w-[52px] text-center text-[11.5px] font-semibold rounded px-0 py-0.5 text-chamber-950"
                                  :class="t.s === 'PASS' ? 'bg-emerald-500' : (t.s === 'FAIL' ? 'bg-rose-500' : '!bg-gray-100 !text-gray-500')" x-text="t.s">WAIT</span>
                            <span class="text-[13px] leading-snug text-gray-800" x-text="t.n"></span>
                        </div>
                    </template>
                    <div class="mt-auto pt-3.5 border-t border-dashed border-gray-200 space-y-1.5">
                        <p class="text-[13px] text-gray-500">passed <span class="text-emerald-500 font-semibold" x-text="passCount">0</span> &middot; failed <span class="text-rose-500 font-semibold" x-text="failCount">0</span> &middot; of 4</p>
                        <div class="h-1.5 rounded bg-gray-100 overflow-hidden" aria-hidden="true"><div class="h-full bg-emerald-500 shadow-[0_0_10px_#4ade80] transition-all" :style="'width:' + (passCount / 4 * 100) + '%'"></div></div>
                        <p class="font-sans text-[13px] leading-snug text-gray-500 pt-1.5">In the headset? You don't log in there. Start a room here, then type its code on the VR keypad.</p>
                    </div>
                </section>
                @endif
            </div>

            <!-- Catchable bugs (decorative easter egg) -->
            <button type="button" x-show="bugs.a" @click="bugs.a = false" aria-label="Catch a hidden bug" class="absolute left-[6%] top-[74%] w-11 h-11 grid place-items-center cursor-crosshair animate-[hud-float_6s_ease-in-out_infinite]">
                <svg width="28" height="28" viewBox="0 0 36 36" fill="#0b1218" stroke="#ff8a3d" stroke-width="2" stroke-linecap="round" aria-hidden="true"><ellipse cx="18" cy="20" rx="7" ry="9"/><path d="M18 11v18M11 17H6M11 23H6M25 17h5M25 23h5M14 12l-3-4M22 12l3-4"/></svg>
            </button>
            <button type="button" x-show="bugs.b" @click="bugs.b = false" aria-label="Catch a hidden bug" class="absolute right-[5%] top-[80%] w-11 h-11 grid place-items-center cursor-crosshair animate-[hud-float_8s_ease-in-out_infinite]">
                <svg width="24" height="24" viewBox="0 0 36 36" fill="#0b1218" stroke="#3fe0c5" stroke-width="2" stroke-linecap="round" aria-hidden="true"><ellipse cx="18" cy="20" rx="7" ry="9"/><path d="M18 11v18M11 17H6M11 23H6M25 17h5M25 23h5M14 12l-3-4M22 12l3-4"/></svg>
            </button>
            <button type="button" x-show="bugs.c" @click="bugs.c = false" aria-label="Catch a hidden bug" class="absolute right-[30%] top-[16%] w-11 h-11 grid place-items-center cursor-crosshair animate-[hud-float_5s_ease-in-out_infinite]">
                <svg width="20" height="20" viewBox="0 0 36 36" fill="#0b1218" stroke="#ff5d5d" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><ellipse cx="18" cy="20" rx="7" ry="9"/><path d="M18 11v18M11 17H6M11 23H6M25 17h5M25 23h5M14 12l-3-4M22 12l3-4"/></svg>
            </button>
        </div>
    </body>
</html>
