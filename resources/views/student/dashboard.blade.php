<x-app-layout>
    <!-- Welcome Header -->
    <x-page-heading
        :title="'Welcome, ' . (Auth::user()?->name ?? 'Student') . '!'"
        description="Pick an escape room mission to start hunting software bugs in VR, or review your test scores below.">
    </x-page-heading>

    @php
        $finishedScores = collect($sessions)->where('status', 'finished')->pluck('score')->filter(fn ($v) => $v !== null);
        $statTiles = [
            ['Sessions played', count($sessions), 'text-gray-900', null],
            ['Escaped', collect($sessions)->where('status', 'finished')->count(), 'text-emerald-500', null],
            ['Average score', $finishedScores->count() ? (int) round($finishedScores->avg()) : '—', 'text-indigo-500', $finishedScores->count() ? '/100' : null],
            ['Best score', $finishedScores->count() ? (int) $finishedScores->max() : '—', 'text-amber-500', $finishedScores->count() ? '/100' : null],
        ];
    @endphp

    <!-- Access-granted strip + next mission -->
    <div class="flex flex-wrap gap-6 items-stretch mb-8">
        <div class="flex-[1_1_340px] flex flex-col justify-center gap-2.5">
            <p class="flex items-center gap-2.5 font-mono text-[12.5px] tracking-[.14em] text-indigo-500">
                <span class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_10px_#4ade80] animate-pulse"></span>ACCESS GRANTED
            </p>
            <p class="text-gray-600">Ready for another run? Pick a room below and generate your code.</p>
        </div>
        @if (count($rooms) > 0)
            <div class="flex-[1_1_340px] relative rounded-xl border-2 border-gray-300 bg-chamber-800 overflow-hidden shadow-[0_0_50px_#ff8a3d1f]">
                <div class="hud-hazard h-2.5" aria-hidden="true"></div>
                <div class="p-6 flex flex-col gap-3">
                    <p class="font-mono text-xs tracking-[.14em] text-amber-500">NEXT MISSION</p>
                    <h2 class="font-display text-2xl font-bold text-gray-900 uppercase">{{ $rooms[0]['name'] }}</h2>
                    <p class="text-sm text-gray-600">Get a one-time code, then type it into the headset.</p>
                    <a href="#rooms" class="self-start inline-flex items-center gap-2.5 min-h-[48px] px-6 rounded-lg bg-amber-500 hover:bg-amber-400 text-chamber-950 font-display font-bold uppercase tracking-[.08em] shadow-[0_0_28px_#ff8a3d59] transition">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 4l14 8-14 8z"/></svg>Pick a room
                    </a>
                </div>
            </div>
        @endif
    </div>

    <!-- Stats -->
    <section aria-label="Your stats" class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
        @foreach ($statTiles as [$label, $value, $color, $suffix])
            <div class="p-5 rounded-xl border border-gray-200 bg-chamber-800/90 flex flex-col gap-1.5">
                <span class="font-mono text-xs tracking-[.12em] uppercase text-gray-500">{{ $label }}</span>
                <span class="font-display text-4xl font-bold {{ $color }}">{{ $value }}@if ($suffix)<span class="text-xl text-gray-500">{{ $suffix }}</span>@endif</span>
            </div>
        @endforeach
    </section>

    <!-- Rank, badges and weak spots (derived from your finished sessions) -->
    @php
        $rank = $profile['rank'];
        $weak = collect($profile['concepts'])->where('is_weak', true);
    @endphp
    <div class="grid lg:grid-cols-3 gap-6 mb-10">
        <x-card class="hud-panel-hot">
            <p class="hud-eyebrow mb-2">// your rank</p>
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-xl border-2 border-amber-500 grid place-items-center font-display text-3xl font-bold text-amber-500 shadow-[0_0_22px_#ff8a3d59]">{{ $rank['level'] }}</div>
                <div>
                    <p class="font-display text-2xl font-bold text-gray-900 uppercase">{{ $rank['name'] }}</p>
                    <p class="font-mono text-xs text-gray-500">{{ $profile['xp'] }} XP</p>
                </div>
            </div>
            <div class="mt-4 hud-bar" role="progressbar" aria-valuenow="{{ $rank['progress'] }}" aria-valuemin="0" aria-valuemax="100"><i style="width: {{ $rank['progress'] }}%"></i></div>
            <p class="mt-2 font-mono text-[11px] text-gray-500">
                @if ($rank['next_name'])
                    {{ $rank['next_xp'] - $profile['xp'] }} XP to {{ $rank['next_name'] }}
                @else
                    Top rank reached
                @endif
            </p>
        </x-card>

        <x-card class="lg:col-span-2" title="Badges" description="Earn them by finishing rooms cleanly.">
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                @foreach ($profile['badges'] as $badge)
                    <div class="p-3 rounded-lg border {{ $badge['earned'] ? 'border-amber-500/60 bg-amber-50' : 'border-gray-100 opacity-60' }}" title="{{ $badge['description'] }}">
                        <p class="font-mono text-[11px] font-semibold uppercase tracking-wider {{ $badge['earned'] ? 'text-amber-500' : 'text-gray-500' }}">
                            {{ $badge['earned'] ? '★ Earned' : '🔒 Locked' }}
                        </p>
                        <p class="font-display font-bold text-gray-900 mt-0.5">{{ $badge['name'] }}</p>
                        <p class="text-xs text-gray-500 mt-0.5 leading-snug">{{ $badge['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </x-card>
    </div>

    <!-- Skill mastery + what to practise next (learner model) -->
    <div class="grid lg:grid-cols-2 gap-6 mb-10">
        <x-card title="Skill mastery" description="How well the system thinks you know each concept. It updates after every puzzle.">
            @if (count($mastery) === 0)
                <p class="text-sm text-gray-500">Play a room and your mastery will appear here.</p>
            @else
                <div class="space-y-4">
                    @foreach ($mastery as $m)
                        <div>
                            <div class="flex justify-between items-baseline text-sm mb-1.5">
                                <span class="text-gray-800">{{ $m['concept'] }}</span>
                                <span class="font-mono text-xs {{ $m['status'] === 'mastered' ? 'text-emerald-500' : ($m['status'] === 'weak' ? 'text-amber-500' : 'text-indigo-500') }}">
                                    {{ $m['percent'] }}% &middot; {{ $m['status'] }}
                                </span>
                            </div>
                            <div class="hud-bar" role="progressbar" aria-valuenow="{{ $m['percent'] }}" aria-valuemin="0" aria-valuemax="100"><i style="width: {{ $m['percent'] }}%; background: {{ $m['status'] === 'mastered' ? '#4ade80' : ($m['status'] === 'weak' ? '#ff8a3d' : '#3fe0c5') }};"></i></div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>

        <x-card title="Recommended next" description="Puzzles picked so they are not too easy and not too hard.">
            @if (count($recommend['next_puzzles']) === 0)
                <p class="text-sm text-gray-500">No puzzles to recommend yet.</p>
            @else
                <ol class="space-y-3 list-none p-0 m-0">
                    @foreach ($recommend['next_puzzles'] as $rec)
                        <li class="p-3 rounded-lg bg-chamber-950 border border-gray-100">
                            <div class="flex justify-between gap-3">
                                <span class="font-semibold text-gray-900">{{ $rec['title'] }} <span class="text-xs text-gray-500 font-normal">&middot; {{ $rec['concept'] }}</span></span>
                                <span class="font-mono text-xs text-indigo-500 whitespace-nowrap">{{ (int) round($rec['predicted_success'] * 100) }}% chance</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">{{ $rec['reason'] }}</p>
                        </li>
                    @endforeach
                </ol>
            @endif
        </x-card>
    </div>

    <x-card class="mb-10" title="Your weak spots" description="Concepts where your average puzzle score is below {{ \App\Services\InsightsService::WEAK_BELOW }}. Practise these next.">
        @if (count($profile['concepts']) === 0)
            <p class="text-sm text-gray-500">Finish a room and your strengths and weak spots will show up here.</p>
        @elseif ($weak->isEmpty())
            <p class="text-sm text-emerald-500 font-mono">&#10003; No weak spots yet. Keep it up!</p>
        @else
            <div class="space-y-4">
                @foreach ($weak as $c)
                    <div>
                        <div class="flex justify-between text-sm mb-1.5">
                            <span class="text-gray-800">{{ $c['concept'] }}</span>
                            <span class="font-mono text-amber-500">{{ $c['avg_score'] }}/100 &middot; {{ $c['avg_hints'] }} hints/puzzle</span>
                        </div>
                        <div class="hud-bar"><i style="width: {{ $c['avg_score'] }}%; background: #ff8a3d;"></i></div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-card>

    @if (!empty($profile['misconceptions']))
        <x-card class="mb-10" title="Mistakes to fix" description="Patterns we noticed in your wrong answers, and how to fix them.">
            <ul class="space-y-3 list-none p-0 m-0">
                @foreach (array_slice($profile['misconceptions'], 0, 3) as $m)
                    <li>
                        <span class="block text-gray-900 font-semibold">{{ $m['label'] }} <span class="font-mono text-xs text-amber-500">&middot; {{ $m['concept'] }}</span></span>
                        <span class="block text-sm text-gray-500">{{ $m['advice'] }}</span>
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif

    <!-- Available Escape Rooms Section -->
    <div id="rooms" class="mb-12 scroll-mt-24">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-5">
            <div>
                <h2 class="font-display text-xl sm:text-2xl font-bold text-gray-900 uppercase tracking-[.02em]">Available Escape Rooms</h2>
                <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Select a challenge to generate your 6-digit VR headset access code.</p>
            </div>
            <span class="inline-flex self-start sm:self-auto items-center px-3 py-1 rounded-sm text-xs font-semibold bg-indigo-50 text-indigo-700">
                {{ count($rooms) }} Rooms Available
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach ($rooms as $room)
                <x-card class="flex flex-col justify-between border-t-4 border-t-indigo-600 hover:shadow-md transition">
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-sm text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                {{ $room['concept_tag'] }}
                            </span>
                            <span class="inline-flex items-center text-xs font-medium text-gray-500 gap-1">
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ $room['time_limit_minutes'] }} mins
                            </span>
                        </div>

                        @php $roomState = $profile['room_status'][$room['id']] ?? 'not_played'; @endphp
                        <span class="inline-block mb-2 font-mono text-[11px] font-semibold px-2 py-0.5 rounded {{ match ($roomState) { 'escaped' => 'bg-emerald-500 text-chamber-950', 'in_progress' => 'bg-amber-500 text-chamber-950', default => 'bg-gray-100 text-gray-600' } }}">
                            {{ match ($roomState) { 'escaped' => 'ESCAPED', 'in_progress' => 'IN PROGRESS', default => 'NOT PLAYED' } }}
                        </span>
                        <h3 class="text-lg font-bold text-gray-900 tracking-tight">{{ $room['name'] }}</h3>
                        <p class="text-sm text-gray-600 mt-2 leading-relaxed">
                            {{ $room['description'] }}
                        </p>
                    </div>

                    <div class="mt-6 pt-4 border-t border-gray-100">
                        <form method="POST" action="{{ route('student.start-room.create', $room['id']) }}">
                            @csrf
                            <x-primary-button type="submit" class="w-full justify-center">
                                Start Room &rarr;
                            </x-primary-button>
                        </form>
                    </div>
                </x-card>
            @endforeach
        </div>
    </div>

    <!-- Past Sessions Section -->
    <div id="past-sessions">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-5">
            <div>
                <h2 class="font-display text-xl sm:text-2xl font-bold text-gray-900 uppercase tracking-[.02em]">My Past Sessions</h2>
                <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Your personal testing session history and performance evaluation reports.</p>
            </div>
            @if (count($sessions) > 0)
                <span class="text-xs text-gray-500 font-medium self-start sm:self-auto">
                    Showing {{ count($sessions) }} {{ Str::plural('session', count($sessions)) }}
                </span>
            @endif
        </div>

        @if (count($sessions) === 0)
            <!-- Friendly Empty State -->
            <x-card class="py-12 text-center border-dashed border-2 border-gray-200">
                <div class="w-16 h-16 mx-auto mb-4 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shadow-xs">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900">No past missions yet!</h3>
                <p class="text-sm text-gray-500 mt-1.5 max-w-md mx-auto">
                    You have not started any VR escape room challenges yet. Pick a room above and click "Start room" to begin your first bug-hunting adventure!
                </p>
            </x-card>
        @else
            <!-- Desktop Table View -->
            <div class="hidden md:block">
                <x-card class="p-0 overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50/75">
                            <tr>
                                <th scope="col" class="px-6 py-3.5 text-left font-mono text-[11px] font-bold text-gray-500 uppercase tracking-wider">Date</th>
                                <th scope="col" class="px-6 py-3.5 text-left font-mono text-[11px] font-bold text-gray-500 uppercase tracking-wider">Room</th>
                                <th scope="col" class="px-6 py-3.5 text-left font-mono text-[11px] font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3.5 text-left font-mono text-[11px] font-bold text-gray-500 uppercase tracking-wider">Score</th>
                                <th scope="col" class="px-6 py-3.5 text-right font-mono text-[11px] font-bold text-gray-500 uppercase tracking-wider">Report</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-transparent">
                            @foreach ($sessions as $session)
                                <tr class="hover:bg-gray-50/60 transition">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800">
                                        {{ $session['date'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                        {{ $session['room_name'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <x-status-badge :status="$session['status']" />
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold {{ $session['score'] !== null ? 'text-gray-900' : 'text-gray-400' }}">
                                        {{ $session['score'] !== null ? $session['score'] . ' / 100' : '—' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        @if ($session['status'] === 'finished')
                                            <a href="{{ route('reports.show', $session['id']) }}" class="text-indigo-600 hover:text-indigo-800 font-semibold inline-flex items-center gap-1">
                                                Open report &rarr;
                                            </a>
                                        @else
                                            <span class="text-gray-400 text-xs">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-card>
            </div>

            <!-- Mobile Stacked Cards View -->
            <div class="block md:hidden space-y-4">
                @foreach ($sessions as $session)
                    <x-card class="p-5">
                        <div class="flex items-start justify-between gap-2 mb-3">
                            <div>
                                <h3 class="text-base font-bold text-gray-900">{{ $session['room_name'] }}</h3>
                                <p class="text-xs text-gray-500 mt-0.5">{{ $session['date'] }}</p>
                            </div>
                            <x-status-badge :status="$session['status']" />
                        </div>

                        <div class="pt-3 border-t border-gray-100 flex items-center justify-between text-sm">
                            <div>
                                <span class="text-xs text-gray-500 font-medium">Score:</span>
                                <span class="font-bold {{ $session['score'] !== null ? 'text-gray-900' : 'text-gray-400' }}">
                                    {{ $session['score'] !== null ? $session['score'] . ' / 100' : '—' }}
                                </span>
                            </div>

                            @if ($session['status'] === 'finished')
                                <a href="{{ route('reports.show', $session['id']) }}" class="text-indigo-600 hover:text-indigo-800 font-semibold text-xs inline-flex items-center min-h-[44px] py-2 px-1 gap-1">
                                    Open report &rarr;
                                </a>
                            @else
                                <span class="text-xs text-gray-400 min-h-[44px] inline-flex items-center">No report</span>
                            @endif
                        </div>
                    </x-card>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
