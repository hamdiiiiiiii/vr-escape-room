<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'BugHunt VR') }} - Software Testing VR Escape Room</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|chakra-petch:500,600,700|jetbrains-mono:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body x-data="{
            solved: false,
            entered: '',
            wrong: false,
            padShake: false,
            hints: 0,
            msg: '',
            shake: false,
            nudge: false,
            puzzle: null,
            puzzles: [
                { unit: 'levels', lo: 1, hi: 5 },
                { unit: 'stars', lo: 2, hi: 6 },
                { unit: 'lives', lo: 3, hi: 7 },
            ],
            phrases: [
                'Whoa there, tester! Crack the door code first.',
                'Door is locked. Solve the puzzle to get the key.',
                'No code, no entry. Solve the puzzle first!',
                'Nice try! Real testers earn their way in.',
            ],
            init() {
                this.puzzle = this.puzzles[Math.floor(Math.random() * this.puzzles.length)];
                try { this.solved = sessionStorage.getItem('bh_gate') === '1'; } catch (e) {}
            },
            get answer() {
                const p = this.puzzle;
                return p ? '' + p.lo + p.hi + (p.lo - 1) + (p.hi + 1) : '';
            },
            press(d) {
                if (this.solved || this.wrong || this.entered.length >= 4) return;
                this.entered += d;
                if (this.entered.length === 4) this.check();
            },
            back() { if (!this.wrong) this.entered = this.entered.slice(0, -1); },
            hint() {
                if (this.solved || this.wrong) return;
                const ok = this.answer.startsWith(this.entered);
                this.entered = this.answer.slice(0, ok ? this.entered.length + 1 : 1);
                this.hints++;
                if (this.entered.length === 4) this.check();
            },
            check() {
                if (this.entered === this.answer) {
                    this.solved = true;
                    try { sessionStorage.setItem('bh_gate', '1'); } catch (e) {}
                } else {
                    this.wrong = true;
                    this.padShake = true;
                    setTimeout(() => { this.padShake = false; }, 650);
                    setTimeout(() => { this.wrong = false; this.entered = ''; }, 900);
                }
            },
            tryLogin(e) {
                if (this.solved) return;
                e.preventDefault();
                this.msg = this.phrases[Math.floor(Math.random() * this.phrases.length)];
                this.shake = true;
                this.nudge = true;
                this.$refs.puzzle && this.$refs.puzzle.scrollIntoView({ behavior: 'smooth', block: 'center' });
                setTimeout(() => { this.shake = false; }, 650);
                setTimeout(() => { this.nudge = false; }, 2100);
                clearTimeout(this._t);
                this._t = setTimeout(() => { this.msg = ''; }, 3200);
            }
        }" class="font-sans antialiased bg-chamber-950 text-gray-700 min-h-screen flex flex-col">
        <!-- Toast: shown when someone presses Log In before solving the puzzle -->
        <div x-show="msg" x-cloak x-transition class="fixed top-20 left-1/2 -translate-x-1/2 z-50 px-5 py-3 bg-chamber-800 border border-amber-500 rounded-lg text-amber-500 font-mono text-sm shadow-glow-amber max-w-[90vw] text-center" role="status">
            <span x-text="msg"></span>
        </div>

        <!-- Top Nav -->
        <nav class="bg-chamber-950/85 backdrop-blur border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <div class="flex items-center">
                    <x-application-logo />
                </div>

                <div class="flex items-center gap-6">
                    <div class="hidden md:flex items-center gap-6 font-mono text-[13.5px] text-gray-600">
                        <a href="#how" class="hover:text-gray-900">How it works</a>
                        <a href="#puzzles" class="hover:text-gray-900">Puzzles</a>
                        <a href="#instructors" class="hover:text-gray-900">For instructors</a>
                    </div>
                    @auth
                        <a href="{{ route('dashboard') }}">
                            <x-primary-button type="button">
                                Go to Dashboard &rarr;
                            </x-primary-button>
                        </a>
                    @else
                        <a href="{{ route('login') }}" @click="tryLogin($event)" :class="shake && 'hud-shake'" class="inline-block">
                            <x-primary-button type="button">
                                <svg x-show="!solved" x-cloak class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="9" rx="1" /><path d="M8 11V8a4 4 0 118 0v3" /></svg>
                                Log In &rarr;
                            </x-primary-button>
                        </a>
                    @endauth
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <main class="relative flex-1 overflow-hidden">
            <div class="hud-floor" aria-hidden="true"></div>

            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16 grid lg:grid-cols-[1.05fr_1fr] gap-12 items-center">
                <!-- Copy -->
                <div>
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-transparent border border-gray-200 text-indigo-500 font-mono text-[11px] sm:text-xs uppercase tracking-wider font-semibold mb-6">
                        <span class="w-2 h-2 rounded-full bg-amber-500 shadow-[0_0_10px_#ff8a3d] animate-pulse"></span>
                        A VR escape room for software testing
                    </div>

                    <h1 class="font-display text-4xl sm:text-6xl font-bold text-gray-900 uppercase leading-[1] hud-glow-text">
                        Find the bug.<br><span class="text-indigo-500">Escape</span><br>the room.
                    </h1>

                    <p class="text-base sm:text-lg text-gray-500 mt-5 max-w-xl">
                        You're locked in a lab full of broken code. Spot the defects, piece together the fixes and crack the final code before time runs out. Every puzzle trains a real testing skill.
                    </p>

                    <p class="font-mono text-sm text-indigo-500 mt-4">
                        &gt; find the bug. unlock the door. escape the room.<span class="hud-cursor ml-1"></span>
                    </p>

                    @auth
                        <div class="mt-8 flex flex-col sm:flex-row items-stretch sm:items-center gap-4">
                            <a href="{{ route('dashboard') }}" class="w-full sm:w-auto">
                                <x-primary-button class="w-full sm:w-auto text-base px-8 py-3.5">
                                    Open Your Dashboard
                                </x-primary-button>
                            </a>
                        </div>
                    @else
                        <!-- Puzzle gate: crack a tiny door code to reveal the login button -->
                        <div x-ref="puzzle" x-show="!solved" :class="nudge && 'hud-nudge'" class="mt-8 hud-panel p-5 max-w-xl">
                            <p class="hud-eyebrow">// unlock the door &middot; crack the code</p>
                            <p class="text-sm text-gray-700 mt-2" x-show="puzzle">
                                A game only allows <span class="text-gray-900 font-semibold" x-text="puzzle && puzzle.unit"></span>
                                from <strong class="text-gray-900" x-text="puzzle && puzzle.lo"></strong>
                                to <strong class="text-gray-900" x-text="puzzle && puzzle.hi"></strong>.
                                Fill the 4 slots to open the door:
                            </p>

                            <div class="mt-4 grid sm:grid-cols-[1fr_auto] gap-5 items-start">
                                <!-- Code slots -->
                                <div :class="padShake && 'hud-shake'" class="grid grid-cols-4 gap-2">
                                    @foreach (['Lowest allowed', 'Highest allowed', 'Just below the lowest', 'Just above the highest'] as $i => $label)
                                        <div class="text-center">
                                            <div class="h-14 grid place-items-center border font-mono text-2xl font-bold transition rounded-lg"
                                                 :class="wrong ? 'border-rose-500 text-rose-500 bg-rose-50' : (entered.length === {{ $i }} ? 'border-amber-500 text-indigo-500 bg-chamber-950' : 'border-indigo-500/60 text-indigo-500 bg-chamber-950')">
                                                <span x-text="entered[{{ $i }}] ?? ''"></span>
                                                <span x-show="entered.length === {{ $i }} && !wrong" class="hud-cursor"></span>
                                            </div>
                                            <p class="mt-1.5 font-mono text-[10px] leading-tight uppercase tracking-wide text-gray-500">{{ $label }}</p>
                                        </div>
                                    @endforeach
                                </div>

                                <!-- Keypad -->
                                <div class="grid grid-cols-3 gap-1.5 w-full sm:w-44">
                                    @foreach ([1,2,3,4,5,6,7,8,9] as $n)
                                        <button type="button" @click="press({{ $n }})" class="min-h-[44px] border border-gray-300 rounded-lg text-indigo-500 hover:border-indigo-500 hover:bg-indigo-50 active:scale-95 font-mono text-lg font-bold transition">{{ $n }}</button>
                                    @endforeach
                                    <button type="button" @click="back()" aria-label="Delete last digit" class="min-h-[44px] border border-gray-300 rounded-lg text-gray-500 hover:text-gray-900 font-mono text-lg transition">&larr;</button>
                                    <button type="button" @click="press(0)" class="min-h-[44px] border border-gray-300 rounded-lg text-indigo-500 hover:border-indigo-500 hover:bg-indigo-50 active:scale-95 font-mono text-lg font-bold transition">0</button>
                                    <button type="button" @click="hint()" class="min-h-[44px] border border-amber-500/50 rounded-lg text-amber-500 hover:bg-amber-50 font-mono text-[11px] font-bold uppercase tracking-wide transition">Hint</button>
                                </div>
                            </div>

                            <p x-show="wrong" x-cloak class="mt-3 font-mono text-xs text-rose-500">&#10005; Wrong code. Look at the 4 labels and try again!</p>
                            <p x-show="hints > 0 && !wrong" x-cloak class="mt-3 font-mono text-xs text-amber-500">Hint used. It fills the next correct digit for you.</p>
                        </div>

                        <div x-show="solved" x-cloak class="mt-8 hud-pop">
                            <p class="font-mono text-sm text-emerald-500 mb-3">
                                &#10003; Door unlocked! You just tested boundary values: the edges of the range, and the numbers just outside it.
                            </p>
                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4">
                                <a href="{{ route('login') }}" class="w-full sm:w-auto">
                                    <x-primary-button class="w-full sm:w-auto text-base px-8 py-3.5">
                                        Log In to Start
                                    </x-primary-button>
                                </a>
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center min-h-[44px] px-6 rounded-lg border border-gray-300 text-gray-800 hover:bg-indigo-50 font-semibold text-sm transition">Create a student account</a>
                                @endif
                            </div>
                        </div>

                        <noscript>
                            <div class="mt-8">
                                <a href="{{ route('login') }}"><x-primary-button class="text-base px-8 py-3.5">Log In to Start</x-primary-button></a>
                            </div>
                        </noscript>
                    @endauth

                    <!-- Concept chips -->
                    <div class="mt-8 flex flex-wrap gap-2">
                        @foreach (['timed rooms', 'hints when stuck', 'AI performance debrief'] as $chip)
                            <span class="font-mono text-[12.5px] px-3 py-1.5 rounded-full border border-gray-200 text-gray-500">{{ $chip }}</span>
                        @endforeach
                    </div>
                </div>

                <!-- Vault door visual (decorative) -->
                <div class="relative hud-float flex justify-center" aria-hidden="true">
                    <div class="relative w-[420px] max-w-full aspect-[4/5] rounded-[18px] border-2 border-gray-300 bg-chamber-800 overflow-hidden flex flex-col shadow-[0_0_0_8px_#070c11,0_0_90px_#3fe0c533,inset_0_0_50px_#000000b3]">
                        <div class="hud-hazard h-4"></div>
                        <div class="hud-scan"></div>
                        <div class="flex-1 flex flex-col items-center justify-center gap-7 p-6">
                            <div class="relative w-[210px] h-[210px]">
                                <svg class="absolute inset-0 animate-[spin_26s_linear_infinite_reverse]" width="210" height="210" viewBox="0 0 210 210" fill="none"><circle cx="105" cy="105" r="100" stroke="#2a4650" stroke-width="2" stroke-dasharray="2 10"/></svg>
                                <svg class="absolute inset-0 animate-[spin_16s_linear_infinite]" width="210" height="210" viewBox="0 0 210 210" fill="none"><circle cx="105" cy="105" r="84" stroke="#3fe0c5" stroke-width="3" stroke-dasharray="40 16" opacity=".75"/></svg>
                                <div class="absolute inset-[52px] rounded-[60px] border-2 border-amber-500 bg-chamber-900 grid place-items-center shadow-[0_0_34px_#ff8a3d73]">
                                    <svg x-show="!solved" width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="#ff8a3d" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 018 0v4M12 15v2"/></svg>
                                    <svg x-show="solved" x-cloak width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="#4ade80" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 017.5-2"/></svg>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                @foreach (['4','8','2','9','1','7'] as $d)
                                    <span class="w-10 h-[52px] rounded-md border-[1.5px] border-amber-500 bg-chamber-950 grid place-items-center font-mono text-[26px] font-semibold text-amber-500 [text-shadow:0_0_12px_#ff8a3d99]">{{ $d }}</span>
                                @endforeach
                            </div>
                            <p class="font-mono text-[12.5px] tracking-[.16em] text-gray-500">door.state = <span :class="solved ? 'text-emerald-500' : 'text-amber-500'" x-text="solved ? 'UNLOCKED' : 'LOCKED'">LOCKED</span></p>
                        </div>
                        <div class="hud-hazard h-2.5 opacity-60"></div>
                    </div>
                </div>
            </div>

            <!-- How it works -->
            <div id="how" class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-16 scroll-mt-20">
                <p class="hud-eyebrow mb-2">// escape route</p>
                <h2 class="font-display text-3xl sm:text-4xl font-bold text-gray-900 uppercase mb-8">How a session works</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5 text-left">
                    <x-card class="hover:border-indigo-500 hover:-translate-y-1 transition">
                        <div class="font-display text-5xl font-bold text-amber-500 leading-none mb-3">01</div>
                        <h3 class="font-display text-xl font-bold text-gray-900">Pick a Room</h3>
                        <p class="text-sm text-gray-600 mt-2">
                            Choose your software testing challenge from the web dashboard.
                        </p>
                    </x-card>

                    <x-card class="hover:border-indigo-500 hover:-translate-y-1 transition">
                        <div class="font-display text-5xl font-bold text-amber-500 leading-none mb-3">02</div>
                        <h3 class="font-display text-xl font-bold text-gray-900">Enter 6-Digit Code</h3>
                        <p class="text-sm text-gray-600 mt-2">
                            Get a 6-digit code on web, then punch it into the VR game keypad.
                        </p>
                    </x-card>

                    <x-card class="hover:border-indigo-500 hover:-translate-y-1 transition">
                        <div class="font-display text-5xl font-bold text-amber-500 leading-none mb-3">03</div>
                        <h3 class="font-display text-xl font-bold text-gray-900">Escape &amp; Learn</h3>
                        <p class="text-sm text-gray-600 mt-2">
                            Solve bugs to open escape doors and receive real-time test reports.
                        </p>
                    </x-card>
                </div>
            </div>

            <!-- Puzzles -->
            <div id="puzzles" class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-16 scroll-mt-20">
                <p class="hud-eyebrow mb-2">// inside the room</p>
                <h2 class="font-display text-3xl sm:text-4xl font-bold text-gray-900 uppercase mb-8">Puzzles that teach testing</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    @foreach ([
                        ['Spot the defect', 'Observation puzzles hide clues and symbols around the room. Find what\'s broken.', 'text-indigo-500', 'M11 4a7 7 0 100 14 7 7 0 000-14zM20 20l-4-4M11 8v3M11 14h.01'],
                        ['Assemble the fix', 'Assembly puzzles make you put the pieces of a solution together in the right order.', 'text-indigo-500', 'M4 4h7v7H4zM13 13h7v7h-7zM13 4h7v7M4 13v7h7'],
                        ['Beat the clock', 'A countdown runs the whole time. Stuck? Take a hint, but it shows in your score.', 'text-amber-500', 'M12 5a8 8 0 100 16 8 8 0 000-16zM12 9v4l2.5 2.5M9 2h6'],
                        ['AI debrief', 'After you escape, AI explains your scores: where you were fast and where to improve.', 'text-amber-500', 'M4 20V10M10 20V4M16 20v-7M22 20H2'],
                    ] as [$t, $d, $c, $path])
                        <x-card class="hover:border-indigo-500 hover:-translate-y-1 transition">
                            <svg class="w-8 h-8 mb-3.5 {{ $c }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $path }}"/></svg>
                            <h3 class="font-display text-lg font-bold text-gray-900">{{ $t }}</h3>
                            <p class="text-sm text-gray-600 mt-2 leading-relaxed">{{ $d }}</p>
                        </x-card>
                    @endforeach
                </div>
            </div>

            <!-- For instructors -->
            <div id="instructors" class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20 scroll-mt-20">
                <div class="flex flex-wrap items-center justify-between gap-6 p-8 rounded-2xl border border-gray-300 bg-chamber-800/90 shadow-[0_0_50px_#3fe0c514]">
                    <div class="flex-1 min-w-[260px]">
                        <p class="hud-eyebrow mb-2">// for instructors</p>
                        <h2 class="font-display text-2xl sm:text-3xl font-bold text-gray-900 uppercase">See how your whole class tests</h2>
                        <p class="text-gray-600 mt-2">Open every student's report and a class summary from your own dashboard.</p>
                    </div>
                    @guest
                        <a href="{{ route('login') }}" class="inline-flex items-center min-h-[48px] px-6 rounded-lg border border-indigo-500 text-indigo-500 hover:bg-indigo-50 font-semibold text-sm transition">Instructor log in</a>
                    @endguest
                </div>
            </div>

            <!-- Crawling bugs (decorative) -->
            <span class="hud-crawl-a pointer-events-none absolute left-[4%] top-[640px]" aria-hidden="true"><svg width="28" height="28" viewBox="0 0 36 36" fill="#0b1218" stroke="#ff8a3d" stroke-width="2" stroke-linecap="round"><ellipse cx="18" cy="20" rx="7" ry="9"/><path d="M18 11v18M11 17H6M11 23H6M25 17h5M25 23h5M14 12l-3-4M22 12l3-4"/></svg></span>
            <span class="hud-crawl-b pointer-events-none absolute right-[6%] top-[1180px]" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 36 36" fill="#0b1218" stroke="#3fe0c5" stroke-width="2" stroke-linecap="round"><ellipse cx="18" cy="20" rx="7" ry="9"/><path d="M18 11v18M11 17H6M11 23H6M25 17h5M25 23h5M14 12l-3-4M22 12l3-4"/></svg></span>
        </main>

        <footer class="border-t border-gray-200 bg-chamber-950/70 py-6 px-6 flex flex-wrap justify-between gap-2 font-mono text-[12.5px] text-gray-500">
            <span>&copy; {{ date('Y') }} BugHunt VR &bull; Graduation Project System</span>
            <span>exit code 0</span>
        </footer>
    </body>
</html>
