<x-app-layout>
    <x-page-heading
        :title="$session['room_name']"
        description="VR Escape Room Session Access & Connection Screen">
        <x-slot name="actions">
            <a href="{{ route('student.dashboard') }}">
                <x-secondary-button type="button">
                    &larr; Back to Dashboard
                </x-secondary-button>
            </a>
        </x-slot>
    </x-page-heading>

    <div
        x-data="{
            status: '{{ $session['status'] }}',
            expiresAt: '{{ $session['expires_at'] }}',
            remainingSeconds: {{ max(0, (int) \Illuminate\Support\Carbon::now()->diffInSeconds(\Illuminate\Support\Carbon::parse($session['expires_at']), false)) }},
            minutes: '{{ sprintf('%02d', max(0, floor(max(0, (int) \Illuminate\Support\Carbon::now()->diffInSeconds(\Illuminate\Support\Carbon::parse($session['expires_at']), false)) / 60))) }}',
            seconds: '{{ sprintf('%02d', max(0, floor(max(0, (int) \Illuminate\Support\Carbon::now()->diffInSeconds(\Illuminate\Support\Carbon::parse($session['expires_at']), false)) % 60))) }}',
            timer: null,
            pollingInterval: null,
            isReconnecting: false,
            isSimulating: false,

            init() {
                this.updateCountdown();
                if (this.status === 'waiting') {
                    this.timer = setInterval(() => {
                        this.updateCountdown();
                    }, 1000);
                }

                if (this.status !== 'expired' && this.status !== 'finished') {
                    this.startPolling();
                }
            },

            updateCountdown() {
                const diff = Math.max(0, Math.floor((new Date(this.expiresAt).getTime() - Date.now()) / 1000));
                this.remainingSeconds = diff;
                const m = Math.floor(diff / 60);
                const s = diff % 60;
                this.minutes = String(m).padStart(2, '0');
                this.seconds = String(s).padStart(2, '0');

                if (diff <= 0 && this.status === 'waiting') {
                    this.status = 'expired';
                    if (this.timer) {
                        clearInterval(this.timer);
                        this.timer = null;
                    }
                    this.stopPolling();
                }
            },

            startPolling() {
                if (this.pollingInterval) return;
                this.pollingInterval = setInterval(() => {
                    this.pollStatus();
                }, 3000);
            },

            stopPolling() {
                if (this.pollingInterval) {
                    clearInterval(this.pollingInterval);
                    this.pollingInterval = null;
                }
            },

            async pollStatus() {
                if (this.status === 'expired' || this.status === 'finished') {
                    this.stopPolling();
                    return;
                }

                try {
                    const res = await fetch('{{ route('student.session.status', $session['id']) }}', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!res.ok) {
                        throw new Error('HTTP error ' + res.status);
                    }

                    const data = await res.json();
                    this.isReconnecting = false;

                    if (data.status && data.status !== this.status) {
                        this.status = data.status;

                        if (this.status === 'in_progress') {
                            if (this.timer) {
                                clearInterval(this.timer);
                                this.timer = null;
                            }
                        }

                        if (this.status === 'expired' || this.status === 'finished') {
                            this.stopPolling();
                            if (this.timer) {
                                clearInterval(this.timer);
                                this.timer = null;
                            }
                        }
                    }

                    if (data.expires_at) {
                        this.expiresAt = data.expires_at;
                    }
                } catch (error) {
                    this.isReconnecting = true;
                }
            },

            async simulateJoin() {
                if (this.isSimulating) return;
                this.isSimulating = true;

                try {
                    const res = await fetch('{{ route('dev.session.simulate-join', $session['id']) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (res.ok) {
                        const data = await res.json();
                        if (data.status) {
                            this.status = data.status;
                            this.isReconnecting = false;
                            if (this.timer) {
                                clearInterval(this.timer);
                                this.timer = null;
                            }
                        }
                    }
                } catch (e) {
                    console.error('Simulate join request failed', e);
                } finally {
                    this.isSimulating = false;
                }
            }
        }"
        class="max-w-3xl mx-auto space-y-6">

        <!-- Top Overview Header Card -->
        <x-card class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Mission Session</span>
                <h2 class="text-xl font-black text-gray-900 mt-0.5">Session #{{ $session['id'] }}</h2>
            </div>

            <div class="flex items-center gap-3">
                <!-- Reconnecting indicator -->
                <span
                    x-show="isReconnecting"
                    class="text-xs text-amber-700 font-semibold inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-50 border border-amber-200"
                    style="display: none;">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                    Reconnecting...
                </span>

                <span class="text-xs text-gray-500 font-medium">Current Status:</span>
                <template x-if="status === 'waiting'">
                    <x-status-badge status="waiting" />
                </template>
                <template x-if="status === 'in_progress'">
                    <x-status-badge status="in_progress" />
                </template>
                <template x-if="status === 'finished'">
                    <x-status-badge status="finished" />
                </template>
                <template x-if="status === 'expired'">
                    <x-status-badge status="expired" />
                </template>

                {{-- Non-JS / initial server fallback --}}
                <noscript>
                    <x-status-badge :status="$session['status']" />
                </noscript>
            </div>
        </x-card>

        <!-- STATE 1: WAITING (Code & Countdown) -->
        <div x-show="status === 'waiting'" @if($session['status'] !== 'waiting') style="display: none;" @endif>
            <x-card class="text-center py-8 sm:py-10 border-t-4 border-t-indigo-600">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                    Waiting for VR Keypad Entry
                </span>

                <h3 class="text-lg font-bold text-gray-900 mt-4">Your 6-Digit VR Access Code</h3>
                <p class="text-xs sm:text-sm text-gray-500 mt-1 max-w-md mx-auto">
                    Type this unique code into the keypad console inside the VR headset to unlock the room doors.
                </p>

                <!-- Big 6-Digit Code Box -->
                <div class="my-6 max-w-sm mx-auto">
                    <div class="bg-indigo-50 border-2 border-indigo-200 rounded-2xl p-6 sm:p-8 shadow-inner text-center">
                        <span class="font-mono text-5xl sm:text-6xl font-black tracking-widest text-indigo-600 select-all">
                            {{ $session['code'] }}
                        </span>
                    </div>
                </div>

                <!-- Short Steps -->
                <div class="max-w-md mx-auto grid grid-cols-1 sm:grid-cols-2 gap-3 text-left">
                    <div class="flex items-center gap-3 p-3.5 rounded-xl bg-gray-50 border border-gray-100">
                        <span class="w-6 h-6 rounded-lg bg-indigo-600 text-white text-xs font-bold flex items-center justify-center shrink-0">1</span>
                        <p class="text-xs sm:text-sm font-semibold text-gray-800 leading-snug">1. Put on the headset.</p>
                    </div>
                    <div class="flex items-center gap-3 p-3.5 rounded-xl bg-gray-50 border border-gray-100">
                        <span class="w-6 h-6 rounded-lg bg-indigo-600 text-white text-xs font-bold flex items-center justify-center shrink-0">2</span>
                        <p class="text-xs sm:text-sm font-semibold text-gray-800 leading-snug">2. Type this code on the keypad.</p>
                    </div>
                </div>

                <!-- 10-Minute Countdown Display -->
                <div class="mt-8 pt-6 border-t border-gray-100 flex flex-col items-center justify-center gap-1.5 text-center">
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Code Expiration Countdown</span>
                    <div class="inline-flex items-center gap-2 font-mono text-2xl sm:text-3xl font-black text-gray-800">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span x-text="minutes + ':' + seconds">
                            {{ sprintf('%02d:%02d', max(0, floor(max(0, (int) \Illuminate\Support\Carbon::now()->diffInSeconds(\Illuminate\Support\Carbon::parse($session['expires_at']), false)) / 60)), max(0, floor(max(0, (int) \Illuminate\Support\Carbon::now()->diffInSeconds(\Illuminate\Support\Carbon::parse($session['expires_at']), false)) % 60))) }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-500">
                        Code expires at {{ \Illuminate\Support\Carbon::parse($session['expires_at'])->format('H:i:s') }}
                    </p>
                </div>
            </x-card>
        </div>

        <!-- STATE 2: IN_PROGRESS -->
        <div x-show="status === 'in_progress'" @if($session['status'] !== 'in_progress') style="display: none;" @endif>
            <x-card class="text-center py-10 sm:py-12 border-t-4 border-t-blue-500">
                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center shadow-xs">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-2xl font-black text-gray-900">You are in the room! Play in VR</h3>
                <p class="text-sm text-gray-600 mt-2 max-w-md mx-auto">
                    Your VR headset is connected to this escape room session. Wear your headset and start finding testing bugs!
                </p>
                <div class="mt-6 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-50 text-blue-700 text-xs font-semibold">
                    <span class="w-2 h-2 rounded-full bg-blue-500 animate-ping"></span>
                    Escape Room In Progress
                </div>
            </x-card>
        </div>

        <!-- STATE 3: FINISHED -->
        <div x-show="status === 'finished'" @if($session['status'] !== 'finished') style="display: none;" @endif>
            <x-card class="text-center py-10 sm:py-12 border-t-4 border-t-emerald-500">
                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shadow-xs">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-2xl font-black text-gray-900">Session Completed!</h3>
                <p class="text-sm text-gray-600 mt-2 max-w-md mx-auto">
                    Great job! You have escaped the room and completed software testing puzzles. Your final evaluation report is ready.
                </p>
                <div class="mt-8 flex justify-center">
                    <a href="{{ route('reports.show', $session['id']) }}">
                        <x-primary-button type="button" class="px-6 py-3">
                            Open report &rarr;
                        </x-primary-button>
                    </a>
                </div>
            </x-card>
        </div>

        <!-- STATE 4: EXPIRED -->
        <div x-show="status === 'expired'" @if($session['status'] !== 'expired') style="display: none;" @endif>
            <x-card class="text-center py-10 sm:py-12 border-t-4 border-t-gray-400">
                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gray-100 text-gray-600 flex items-center justify-center shadow-xs">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-2xl font-black text-gray-900">This code has expired</h3>
                <p class="text-sm text-gray-500 mt-2 max-w-md mx-auto">
                    The 10-minute countdown window passed before the code was entered on the VR keypad. Click below to generate a fresh code!
                </p>
                <div class="mt-8 flex justify-center">
                    <form method="POST" action="{{ route('student.start-room.create', $session['room_id']) }}">
                        @csrf
                        <x-primary-button type="submit" class="px-6 py-3">
                            Get new code &rarr;
                        </x-primary-button>
                    </form>
                </div>
            </x-card>
        </div>

        <!-- Single Dev Tool Button (Local only, async fetch, no whole page reload) -->
        @if (app()->environment('local'))
            <div x-show="status === 'waiting'" class="p-4 rounded-2xl bg-amber-50/80 border border-amber-200 text-left text-xs text-amber-900">
                <span class="font-bold uppercase tracking-wider text-[10px] text-amber-700">Dev Tool &bull; Local Only</span>
                <p class="mt-1 text-amber-800">
                    Simulate headset connection without a VR headset:
                </p>
                <div class="mt-2.5">
                    <button
                        type="button"
                        @click="simulateJoin()"
                        :disabled="isSimulating"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-600 hover:bg-amber-700 disabled:opacity-50 text-white font-semibold transition text-xs shadow-xs cursor-pointer">
                        <span x-text="isSimulating ? 'Connecting...' : 'Simulate headset joined (dev only)'">Simulate headset joined (dev only)</span>
                    </button>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
