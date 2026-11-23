<x-app-layout>
    <!-- Page Heading -->
    <x-page-heading
        title="Instructor Dashboard"
        description="Review assigned students, track escape room challenge sessions, and inspect student test reports.">
    </x-page-heading>

    <!-- Overview Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
        <!-- Assigned Students -->
        <x-card class="border-l-4 border-l-indigo-600">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <p class="font-mono text-[11px] font-bold text-indigo-600 uppercase tracking-wider">Assigned Students</p>
                    <h3 class="font-display text-2xl font-bold text-gray-900 mt-1">{{ $overview['total_students'] }} Students</h3>
                </div>
                <div class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-3">Registered in your classroom roster.</p>
        </x-card>

        <!-- In Progress Sessions -->
        <x-card class="border-l-4 border-l-blue-600">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <p class="font-mono text-[11px] font-bold text-blue-600 uppercase tracking-wider">In Progress</p>
                    <h3 class="font-display text-2xl font-bold text-gray-900 mt-1">{{ $overview['in_progress_count'] }} {{ Str::plural('Session', $overview['in_progress_count']) }}</h3>
                </div>
                <x-status-badge status="in_progress" />
            </div>
            <p class="text-xs text-gray-500 mt-3">Currently active escape room sessions.</p>
        </x-card>

        <!-- Waiting Sessions -->
        <x-card class="border-l-4 border-l-amber-500">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <p class="font-mono text-[11px] font-bold text-amber-600 uppercase tracking-wider">Waiting to Start</p>
                    <h3 class="font-display text-2xl font-bold text-gray-900 mt-1">{{ $overview['waiting_count'] }} {{ Str::plural('Session', $overview['waiting_count']) }}</h3>
                </div>
                <x-status-badge status="waiting" />
            </div>
            <p class="text-xs text-gray-500 mt-3">Awaiting VR headset code entry.</p>
        </x-card>

        <!-- Finished Sessions -->
        <x-card class="border-l-4 border-l-emerald-500">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <p class="font-mono text-[11px] font-bold text-emerald-600 uppercase tracking-wider">Completed Sessions</p>
                    <h3 class="font-display text-2xl font-bold text-gray-900 mt-1">{{ $overview['finished_count'] }} {{ Str::plural('Session', $overview['finished_count']) }}</h3>
                </div>
                <x-status-badge status="finished" />
            </div>
            <p class="text-xs text-gray-500 mt-3">Finished escape room challenges.</p>
        </x-card>
    </div>

    <!-- Class Performance Analytics (Phase 5) -->
    <!-- STUB: data sourced from InstructorService + ReportService stub records. -->
    <!-- Replace $analytics data source when Member 5 delivers Eloquent session and concept-score relations. -->
    <div class="mb-10">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-5">
            <div>
                <h2 class="font-display text-xl sm:text-2xl font-bold text-gray-900 uppercase tracking-[.02em]">Class Performance</h2>
                <p class="text-xs sm:text-sm text-gray-500 mt-0.5">
                    Summary analytics for your assigned students' completed sessions.
                </p>
            </div>
            <span class="inline-flex items-center px-2.5 py-1 rounded-sm text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                Demo data &mdash; stub records only
            </span>
        </div>

        @if ($analytics['class_average'] === null)
            {{-- Empty state: no finished sessions with valid scores --}}
            <x-card class="py-10 text-center border-dashed border-2 border-gray-200">
                <div class="w-14 h-14 mx-auto mb-3 rounded-lg bg-gray-50 text-gray-400 flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-gray-900">No completed sessions yet</h3>
                <p class="text-xs text-gray-500 mt-1 max-w-xs mx-auto">
                    Class analytics will appear once at least one assigned student completes a session with a valid score.
                </p>
            </x-card>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

                {{-- Card 1: Class Average Score --}}
                <x-card class="border-l-4 border-l-indigo-600">
                    <p class="font-mono text-[11px] font-bold text-indigo-600 uppercase tracking-wider">Class Average Score</p>
                    <div class="flex flex-wrap items-end gap-3 mt-2">
                        <h3 class="font-display text-3xl font-bold text-gray-900">
                            {{ $analytics['class_average'] }}<span class="text-base font-semibold text-gray-400"> / 100</span>
                        </h3>
                        @php
                            $levelClasses = match ($analytics['class_average_level']) {
                                'Advanced'      => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                'Proficient'    => 'bg-blue-50 text-blue-700 border-blue-100',
                                default         => 'bg-red-50 text-red-700 border-red-100',
                            };
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-sm text-xs font-semibold border {{ $levelClasses }}">
                            {{ $analytics['class_average_level'] }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 mt-3">
                        Based on <strong>{{ $analytics['finished_session_count'] }}</strong>
                        {{ Str::plural('completed session', $analytics['finished_session_count']) }} with valid scores.
                    </p>
                </x-card>

                {{-- Card 2: Lowest-Scoring Concept --}}
                <x-card class="border-l-4 border-l-rose-500">
                    <p class="font-mono text-[11px] font-bold text-rose-600 uppercase tracking-wider">Lowest-Scoring Concept</p>
                    @if ($analytics['lowest_concept'] === null)
                        <p class="text-sm text-gray-500 mt-3 italic">Concept score data unavailable for completed sessions.</p>
                    @else
                        <div class="mt-2">
                            <h3 class="text-base font-bold text-gray-900 leading-snug break-words">
                                {{ $analytics['lowest_concept'] }}
                            </h3>
                            <p class="font-display text-3xl font-bold text-gray-900 mt-1">
                                {{ $analytics['lowest_concept_avg'] }}<span class="text-base font-semibold text-gray-400"> / 100</span>
                            </p>
                        </div>
                        <p class="text-xs text-gray-500 mt-3">
                            Avg concept score from
                            <strong>{{ $analytics['lowest_concept_sessions'] }}</strong>
                            {{ Str::plural('session', $analytics['lowest_concept_sessions']) }}.
                            Concept tags are <span class="italic">provisional</span> &mdash; subject to team approval.
                        </p>
                        <p class="text-[10px] text-gray-400 mt-1">
                            Lowest-scoring = lowest avg concept score, not missed-attempt count.
                        </p>
                    @endif
                </x-card>

            </div>
        @endif
    </div>

    <!-- Class briefing: what to teach next -->
    <x-card class="mb-10" title="Class briefing" description="What to teach next, worked out from your students' results.">
        @if (session('briefing_ok'))<p role="status" class="mb-3 text-sm text-emerald-500">{{ session('briefing_ok') }}</p>@endif
        @if (session('briefing_error'))<p role="alert" class="mb-3 text-sm text-rose-500">{{ session('briefing_error') }}</p>@endif

        @if (! $briefing['has_data'])
            <p class="text-sm text-gray-500">{{ $briefing['headline'] }} It appears once your students finish rooms.</p>
        @else
            <p class="font-display text-lg font-bold text-gray-900 uppercase">{{ $briefing['headline'] }}</p>
            <ul class="mt-3 space-y-2 list-disc pl-5 text-sm text-gray-700">
                @foreach ($briefing['points'] as $point)<li>{{ $point }}</li>@endforeach
            </ul>
            @if (!empty($briefing['attention']))
                <p class="hud-eyebrow mt-5 mb-2">NEEDS_ATTENTION</p>
                <ul class="space-y-1 list-none p-0 m-0 text-sm">
                    @foreach ($briefing['attention'] as $a)
                        <li class="flex justify-between gap-3"><span class="text-gray-800">{{ $a['name'] }}</span><span class="font-mono text-xs text-amber-500">{{ $a['concept'] }} &middot; {{ $a['percent'] }}%</span></li>
                    @endforeach
                </ul>
            @endif
            @if ($briefingNarrative)
                <p class="mt-5 p-3 rounded-lg border border-gray-200 bg-chamber-950 text-sm text-gray-700 leading-relaxed"><span class="hud-eyebrow block mb-1">AI_SUMMARY</span>{{ $briefingNarrative }}</p>
            @endif
            <form method="POST" action="{{ route('instructor.class-briefing.write') }}" class="mt-4">
                @csrf
                <x-secondary-button type="submit" :disabled="! $briefingAiReady">Write summary with AI</x-secondary-button>
                @if (! $briefingAiReady)<span class="ml-2 font-mono text-xs text-gray-500">needs an API key</span>@endif
            </form>
        @endif
    </x-card>

    <!-- Where the class gets stuck (from puzzle_results of your students) -->
    <x-card class="mb-10" title="Where the class gets stuck" description="Average time per puzzle across your students. Slowest first.">
        @if (empty($stuck))
            <p class="text-sm text-gray-500">No puzzle data yet. It appears once your students finish rooms.</p>
        @else
            @php $maxT = max(1, collect($stuck)->max('avg_time_seconds')); @endphp
            <div class="space-y-4">
                @foreach ($stuck as $i => $row)
                    <div>
                        <div class="flex flex-wrap justify-between gap-2 text-sm mb-1.5">
                            <span class="text-gray-800">{{ $row['title'] }} <span class="text-gray-500 text-xs">&middot; {{ $row['concept'] }}</span></span>
                            <span class="font-mono {{ $i === 0 ? 'text-amber-500' : 'text-indigo-500' }}">
                                {{ intdiv($row['avg_time_seconds'], 60) }}m {{ $row['avg_time_seconds'] % 60 }}s &middot; {{ $row['avg_hints'] }} hints &middot; {{ $row['fail_rate'] }}% fail
                            </span>
                        </div>
                        <div class="hud-bar"><i style="width: {{ round($row['avg_time_seconds'] / $maxT * 100) }}%; background: {{ $i === 0 ? '#ff8a3d' : '#3fe0c5' }};"></i></div>
                        @if (!empty($row['common_wrong']))
                            <p class="mt-1.5 font-mono text-[11px] text-gray-500">
                                Common wrong answers:
                                @foreach ($row['common_wrong'] as $w)
                                    <span class="text-rose-500">{{ $w['answer'] }}</span> ({{ $w['count'] }}x){{ $loop->last ? '' : ',' }}
                                @endforeach
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </x-card>

    <!-- Common misconceptions (named from the wrong answers) -->
    <x-card class="mb-10" title="Common misconceptions" description="What your students' wrong answers have in common. Use these to decide what to teach next.">
        @if (empty($misconceptions))
            <p class="text-sm text-gray-500">No wrong answers recorded yet. They appear once the headset game sends them.</p>
        @else
            <ul class="space-y-3 list-none p-0 m-0">
                @foreach ($misconceptions as $m)
                    <li class="flex flex-wrap justify-between gap-2">
                        <span>
                            <span class="block text-gray-900 font-semibold">{{ $m['label'] }}</span>
                            <span class="block text-sm text-gray-500">{{ $m['advice'] }}</span>
                        </span>
                        <span class="font-mono text-xs text-amber-500 whitespace-nowrap">{{ $m['concept'] }} &middot; {{ $m['count'] }}x</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>

    <!-- Assigned Students Roster Section -->
    <div class="mb-12">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h2 class="font-display text-xl sm:text-2xl font-bold text-gray-900 uppercase tracking-[.02em]">Assigned Students</h2>
                <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Students enrolled in your software testing class.</p>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-sm text-xs font-semibold bg-indigo-50 text-indigo-700">
                {{ count($students) }} {{ Str::plural('Student', count($students)) }}
            </span>
        </div>

        @if (count($students) === 0)
            <x-card class="py-12 text-center border-dashed border-2 border-gray-200">
                <div class="w-16 h-16 mx-auto mb-4 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900">No students assigned</h3>
                <p class="text-sm text-gray-500 mt-1 max-w-md mx-auto">There are currently no students assigned to your instructor roster.</p>
            </x-card>
        @else
            <!-- Desktop Table View -->
            <div class="hidden md:block">
                <x-card class="p-0 overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50/75">
                            <tr>
                                <th scope="col" class="px-6 py-3.5 text-left font-mono text-[11px] font-bold text-gray-500 uppercase tracking-wider">Student Name</th>
                                <th scope="col" class="px-6 py-3.5 text-left font-mono text-[11px] font-bold text-gray-500 uppercase tracking-wider">Email Address</th>
                                <th scope="col" class="px-6 py-3.5 text-left font-mono text-[11px] font-bold text-gray-500 uppercase tracking-wider">Recorded Sessions</th>
                                <th scope="col" class="px-6 py-3.5 text-left font-mono text-[11px] font-bold text-gray-500 uppercase tracking-wider">Last Active</th>
                                <th scope="col" class="px-6 py-3.5 text-right font-mono text-[11px] font-bold text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-transparent">
                            @foreach ($students as $student)
                                <tr class="hover:bg-gray-50/60 transition">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 font-bold text-xs flex items-center justify-center">
                                                {{ strtoupper(substr($student['name'], 0, 1)) }}
                                            </div>
                                            <span>{{ $student['name'] }}</span>
                                            @if (isset($studentMeta[$student['id']]))
                                                <span class="font-mono text-[10px] uppercase tracking-wider px-1.5 py-0.5 rounded border border-amber-500/50 text-amber-500">{{ $studentMeta[$student['id']]['rank'] }}</span>
                                                @if ($studentMeta[$student['id']]['weakest'])
                                                    <span class="font-mono text-[10px] uppercase tracking-wider px-1.5 py-0.5 rounded border border-rose-500/40 text-rose-500" title="Weakest concept">weak: {{ $studentMeta[$student['id']]['weakest'] }}</span>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ $student['email'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800">
                                        {{ $student['session_count'] }} {{ Str::plural('session', $student['session_count']) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $student['last_active'] ?? 'Never' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        @if ($student['session_count'] > 0)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-sm text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-100">
                                                Active Learner
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-sm text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">
                                                No sessions recorded
                                            </span>
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
                @foreach ($students as $student)
                    <x-card class="p-5">
                        <div class="flex items-start justify-between gap-2 mb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-indigo-100 text-indigo-700 font-bold text-sm flex items-center justify-center">
                                    {{ strtoupper(substr($student['name'], 0, 1)) }}
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-gray-900">{{ $student['name'] }}</h3>
                                    <p class="text-xs text-gray-500">{{ $student['email'] }}</p>
                                </div>
                            </div>
                            @if ($student['session_count'] > 0)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-sm text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-sm text-[10px] font-semibold bg-gray-100 text-gray-600 border border-gray-200">
                                    No sessions
                                </span>
                            @endif
                        </div>

                        <div class="pt-3 border-t border-gray-100 flex flex-wrap items-center justify-between gap-2 text-xs text-gray-600">
                            <span><strong>{{ $student['session_count'] }}</strong> {{ Str::plural('session', $student['session_count']) }} recorded</span>
                            <span>Last active: <strong>{{ $student['last_active'] ?? 'Never' }}</strong></span>
                        </div>
                    </x-card>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Student Sessions Section with Alpine.js Filtering -->
    <div x-data="{ activeFilter: 'all' }">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-5">
            <div>
                <h2 class="font-display text-xl sm:text-2xl font-bold text-gray-900 uppercase tracking-[.02em]">Student Sessions</h2>
                <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Recorded escape room runs across assigned students.</p>
            </div>

            <!-- Simple Alpine.js Status Filter -->
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    @click="activeFilter = 'all'"
                    :class="activeFilter === 'all' ? 'bg-indigo-600 text-slate-50 shadow-xs' : 'bg-chamber-800 text-gray-700 hover:bg-gray-100 border border-gray-200'"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition">
                    All ({{ count($sessions) }})
                </button>
                <button
                    type="button"
                    @click="activeFilter = 'in_progress'"
                    :class="activeFilter === 'in_progress' ? 'bg-blue-600 text-slate-50 shadow-xs' : 'bg-chamber-800 text-gray-700 hover:bg-gray-100 border border-gray-200'"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition">
                    In Progress ({{ $overview['in_progress_count'] }})
                </button>
                <button
                    type="button"
                    @click="activeFilter = 'waiting'"
                    :class="activeFilter === 'waiting' ? 'bg-amber-500 text-slate-50 shadow-xs' : 'bg-chamber-800 text-gray-700 hover:bg-gray-100 border border-gray-200'"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition">
                    Waiting ({{ $overview['waiting_count'] }})
                </button>
                <button
                    type="button"
                    @click="activeFilter = 'finished'"
                    :class="activeFilter === 'finished' ? 'bg-emerald-600 text-slate-50 shadow-xs' : 'bg-chamber-800 text-gray-700 hover:bg-gray-100 border border-gray-200'"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition">
                    Finished ({{ $overview['finished_count'] }})
                </button>
            </div>
        </div>

        @if (count($sessions) === 0)
            <x-card class="py-12 text-center border-dashed border-2 border-gray-200">
                <div class="w-16 h-16 mx-auto mb-4 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900">No session records</h3>
                <p class="text-sm text-gray-500 mt-1 max-w-md mx-auto">No student escape room sessions have been recorded yet.</p>
            </x-card>
        @else
            <!-- Desktop Table View -->
            <div class="hidden md:block">
                <x-card class="p-0 overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50/75">
                            <tr>
                                <th scope="col" class="px-6 py-3.5 text-left font-mono text-[11px] font-bold text-gray-500 uppercase tracking-wider">Date</th>
                                <th scope="col" class="px-6 py-3.5 text-left font-mono text-[11px] font-bold text-gray-500 uppercase tracking-wider">Student</th>
                                <th scope="col" class="px-6 py-3.5 text-left font-mono text-[11px] font-bold text-gray-500 uppercase tracking-wider">Escape Room</th>
                                <th scope="col" class="px-6 py-3.5 text-left font-mono text-[11px] font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3.5 text-left font-mono text-[11px] font-bold text-gray-500 uppercase tracking-wider">Score</th>
                                <th scope="col" class="px-6 py-3.5 text-right font-mono text-[11px] font-bold text-gray-500 uppercase tracking-wider">Report</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-transparent">
                            @foreach ($sessions as $session)
                                <tr
                                    x-show="activeFilter === 'all' || activeFilter === '{{ $session['status'] }}'"
                                    class="hover:bg-gray-50/60 transition">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800">
                                        {{ $session['date'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-gray-900">{{ $session['student_name'] }}</div>
                                        <div class="text-xs text-gray-500">{{ $session['student_email'] }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-gray-900">{{ $session['room_name'] }}</div>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-indigo-50 text-indigo-700 mt-0.5">
                                            {{ $session['concept_tag'] }}
                                        </span>
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
                                                View report &rarr;
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
                    <div x-show="activeFilter === 'all' || activeFilter === '{{ $session['status'] }}'">
                        <x-card class="p-5">
                            <div class="flex items-start justify-between gap-2 mb-3">
                                <div>
                                    <h3 class="text-base font-bold text-gray-900">{{ $session['student_name'] }}</h3>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ $session['room_name'] }} • {{ $session['date'] }}</p>
                                    <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-indigo-50 text-indigo-700">
                                        {{ $session['concept_tag'] }}
                                    </span>
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
                                    <a href="{{ route('reports.show', $session['id']) }}" class="text-indigo-600 hover:text-indigo-800 font-semibold text-xs inline-flex items-center gap-1">
                                        View report &rarr;
                                    </a>
                                @else
                                    <span class="text-xs text-gray-400">No report</span>
                                @endif
                            </div>
                        </x-card>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
