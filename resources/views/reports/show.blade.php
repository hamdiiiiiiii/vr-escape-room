<x-app-layout>
    <!-- Page Heading -->
    <x-page-heading
        title="Session Performance Report"
        :description="'Session #' . $report['id'] . ' • ' . $report['room_name'] . ' • Completed on ' . $report['date']">
        <x-slot name="actions">
            @if ($isInstructor)
                <a href="{{ route('instructor.dashboard') }}">
                    <x-secondary-button type="button">
                        &larr; Back to Instructor Dashboard
                    </x-secondary-button>
                </a>
            @else
                <a href="{{ route('student.dashboard') }}">
                    <x-secondary-button type="button">
                        &larr; Back to Student Dashboard
                    </x-secondary-button>
                </a>
            @endif
        </x-slot>
    </x-page-heading>

    <!-- Top Metric Cards (4 KPIs) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
        <!-- Overall Score & Tier -->
        <x-card class="border-l-4 {{ $report['score'] >= 80 ? 'border-l-emerald-500' : ($report['score'] >= 50 ? 'border-l-blue-500' : 'border-l-rose-500') }}">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="font-mono text-[11px] font-bold text-gray-500 uppercase tracking-wider">Evaluation Score</p>
                    <p class="font-display text-3xl font-bold text-gray-900 mt-1">{{ $report['score'] }} / 100</p>
                </div>
                <div class="hud-ring shrink-0" aria-hidden="true"
                     style="--pct: {{ max(0, min(100, (int) $report['score'])) }}; --ring: {{ $report['score'] >= 80 ? '#4ade80' : ($report['score'] >= 50 ? '#3fe0c5' : '#ff5d5d') }}">
                    <span class="text-lg">{{ (int) $report['score'] }}</span>
                </div>
            </div>
            <div class="mt-3">
                @if ($report['score_level'] === 'Advanced')
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-sm text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-sm bg-emerald-500"></span>
                        Advanced (80–100)
                    </span>
                @elseif ($report['score_level'] === 'Proficient')
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-sm text-xs font-semibold bg-blue-100 text-blue-800 border border-blue-200">
                        <span class="w-1.5 h-1.5 rounded-sm bg-blue-500"></span>
                        Proficient (50–79)
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-sm text-xs font-semibold bg-rose-100 text-rose-800 border border-rose-200">
                        <span class="w-1.5 h-1.5 rounded-sm bg-rose-500"></span>
                        Needs Support (0–49)
                    </span>
                @endif
            </div>
        </x-card>

        <!-- Total Time Spent -->
        <x-card class="border-l-4 border-l-indigo-600">
            <p class="font-mono text-[11px] font-bold text-gray-500 uppercase tracking-wider">Total Time</p>
            <p class="font-display text-3xl font-bold text-gray-900 mt-1">{{ $report['time_spent'] }}</p>
            <p class="text-xs text-gray-500 mt-3">Time limit: {{ $report['time_limit_minutes'] }} minutes</p>
        </x-card>

        <!-- Puzzles Solved & Attempts -->
        <x-card class="border-l-4 border-l-purple-600">
            <p class="font-mono text-[11px] font-bold text-gray-500 uppercase tracking-wider">Puzzles Solved</p>
            <p class="font-display text-3xl font-bold text-gray-900 mt-1">{{ $report['puzzles_solved'] }} / {{ $report['total_puzzles'] }}</p>
            <p class="text-xs text-gray-500 mt-3">Total attempts: {{ $report['total_attempts'] }}</p>
        </x-card>

        <!-- Solved Without Hints -->
        <x-card class="border-l-4 border-l-amber-500">
            <p class="font-mono text-[11px] font-bold text-gray-500 uppercase tracking-wider">Without Hints</p>
            <p class="font-display text-3xl font-bold text-gray-900 mt-1">{{ $report['puzzles_without_hints'] }} / {{ $report['total_puzzles'] }}</p>
            <p class="text-xs text-gray-500 mt-3">Hints used: {{ $report['hints_used'] }}</p>
        </x-card>
    </div>

    <!-- Details Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">
        <!-- Session & Student Overview -->
        <x-card title="Session Details" description="Student information and challenge environment parameters.">
            <div class="space-y-4 pt-2">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between py-2 border-b border-gray-100 gap-1 sm:gap-2">
                    <span class="text-sm font-medium text-gray-500">Student</span>
                    <span class="text-sm font-bold text-gray-900 break-words text-left sm:text-right">{{ $report['student_name'] }} ({{ $report['student_email'] }})</span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                    <span class="text-sm font-medium text-gray-500">Escape Room</span>
                    <span class="text-sm font-bold text-gray-900">{{ $report['room_name'] }}</span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                    <span class="text-sm font-medium text-gray-500">Primary Concept</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold bg-indigo-50 text-indigo-700">
                        {{ $report['concept_tag'] }}
                    </span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                    <span class="text-sm font-medium text-gray-500">Completion Date</span>
                    <span class="text-sm font-medium text-gray-900">{{ $report['date'] }}</span>
                </div>
                <div class="flex items-center justify-between pt-2">
                    <span class="text-sm font-medium text-gray-500">Evaluation Status</span>
                    <x-status-badge :status="$report['status']" />
                </div>
            </div>
        </x-card>

        <!-- Software Testing Concept Breakdown -->
        <x-card title="Testing Concepts Breakdown" description="Evaluation scores across testing objectives.">
            <div class="mb-4 p-3 rounded-lg bg-amber-50 border border-amber-200">
                <p class="text-xs font-medium text-amber-800">
                    <strong class="font-bold">Provisional Topics:</strong> These concepts reflect provisional room mappings and will be aligned once the curriculum list is finalized with the team.
                </p>
            </div>

            <div class="space-y-4">
                @foreach ($report['concept_scores'] as $conceptScore)
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="font-semibold text-gray-800">{{ $conceptScore['concept'] }}</span>
                            <span class="font-bold text-indigo-700">{{ $conceptScore['score'] }}%</span>
                        </div>
                        <div class="w-full bg-chamber-950 border border-indigo-500/20 rounded-sm h-2.5 overflow-hidden">
                            <div class="h-2.5 rounded-sm transition-all duration-300 {{ $conceptScore['score'] >= 80 ? 'bg-indigo-500 shadow-[0_0_10px_#3fe0c5]' : ($conceptScore['score'] >= 50 ? 'bg-amber-500 shadow-[0_0_10px_#ff8a3d]' : 'bg-rose-500 shadow-[0_0_10px_#ff5d5d]') }}" style="width: {{ $conceptScore['score'] }}%"></div>
                        </div>
                        <p class="font-mono text-[11px] text-gray-500 mt-1">{{ $conceptScore['note'] }}</p>
                    </div>
                @endforeach
            </div>
        </x-card>
    </div>

    <!-- Puzzle log: your time per puzzle compared with everyone who played it -->
    @if (!empty($puzzleLog))
        <x-card class="mb-8 p-0 overflow-hidden" title="Puzzle log" description="Each puzzle, your time against the class average.">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="font-mono text-[11px] uppercase tracking-wider text-gray-500 text-left">
                            <th class="px-5 py-3">Stage</th><th class="px-5 py-3">Puzzle</th><th class="px-5 py-3">Time</th>
                            <th class="px-5 py-3 w-40">Vs class avg</th><th class="px-5 py-3">Tries</th><th class="px-5 py-3">Hints</th><th class="px-5 py-3">Result</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($puzzleLog as $row)
                            <tr>
                                <td class="px-5 py-3 font-mono text-gray-500">{{ $row['stage'] }}</td>
                                <td class="px-5 py-3 text-gray-900">{{ $row['title'] }}<br><span class="text-xs text-gray-500">{{ $row['type'] }}@if (!empty($row['wrong'])) &middot; tried: <span class="text-rose-500">{{ implode(', ', $row['wrong']) }}</span>@endif</span></td>
                                <td class="px-5 py-3 font-mono">{{ $row['time'] }}</td>
                                <td class="px-5 py-3">
                                    <div class="hud-bar !h-2"><i style="width: {{ $row['bar'] }}%; background: {{ $row['vs_class'] === 'slower' ? '#ff8a3d' : ($row['vs_class'] === 'faster' ? '#4ade80' : '#3fe0c5') }};"></i></div>
                                    <span class="font-mono text-[11px] {{ $row['vs_class'] === 'slower' ? 'text-amber-500' : ($row['vs_class'] === 'faster' ? 'text-emerald-500' : 'text-indigo-500') }}">{{ $row['vs_class'] }}</span>
                                </td>
                                <td class="px-5 py-3 font-mono">{{ $row['attempts'] }}</td>
                                <td class="px-5 py-3 font-mono {{ $row['hints'] > 0 ? 'text-amber-500' : '' }}">{{ $row['hints'] }}</td>
                                <td class="px-5 py-3 font-mono text-xs font-semibold {{ $row['solved'] ? 'text-emerald-500' : 'text-rose-500' }}">{{ $row['solved'] ? 'PASS' : 'FAIL' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    @endif

    <!-- AI Analysis Section (Empty State / Friendly Message) -->
    <x-card title="AI Tutor & Bug Hunting Analysis" description="Automated bug analysis, time-to-fix diagnostics, and personalized guidance.">
        <div class="py-12 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <h3 class="text-base font-bold text-gray-900">AI Analysis Not Available Yet</h3>
            <p class="text-sm text-gray-500 mt-2 max-w-lg mx-auto leading-relaxed">
                Automated code-level bug hunting recommendations and AI tutor analysis will be available in an upcoming update once the evaluation engine is integrated.
            </p>
            <div class="mt-6">
                <span class="inline-flex items-center px-3 py-1 rounded-sm text-xs font-semibold bg-gray-100 text-gray-600 border border-gray-200">
                    Engine Status: Scheduled for Future Phase
                </span>
            </div>
        </div>
    </x-card>
</x-app-layout>
