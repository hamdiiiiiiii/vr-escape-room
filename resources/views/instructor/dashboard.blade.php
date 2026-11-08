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
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Assigned Students</p>
                    <h3 class="text-2xl font-black text-gray-900 mt-1">{{ $overview['total_students'] }} Students</h3>
                </div>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-3">Registered in your classroom roster.</p>
        </x-card>

        <!-- In Progress Sessions -->
        <x-card class="border-l-4 border-l-blue-600">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-blue-600 uppercase tracking-wider">In Progress</p>
                    <h3 class="text-2xl font-black text-gray-900 mt-1">{{ $overview['in_progress_count'] }} {{ Str::plural('Session', $overview['in_progress_count']) }}</h3>
                </div>
                <x-status-badge status="in_progress" />
            </div>
            <p class="text-xs text-gray-500 mt-3">Currently active escape room sessions.</p>
        </x-card>

        <!-- Waiting Sessions -->
        <x-card class="border-l-4 border-l-amber-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-amber-600 uppercase tracking-wider">Waiting to Start</p>
                    <h3 class="text-2xl font-black text-gray-900 mt-1">{{ $overview['waiting_count'] }} {{ Str::plural('Session', $overview['waiting_count']) }}</h3>
                </div>
                <x-status-badge status="waiting" />
            </div>
            <p class="text-xs text-gray-500 mt-3">Awaiting VR headset code entry.</p>
        </x-card>

        <!-- Finished Sessions -->
        <x-card class="border-l-4 border-l-emerald-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Completed Sessions</p>
                    <h3 class="text-2xl font-black text-gray-900 mt-1">{{ $overview['finished_count'] }} {{ Str::plural('Session', $overview['finished_count']) }}</h3>
                </div>
                <x-status-badge status="finished" />
            </div>
            <p class="text-xs text-gray-500 mt-3">Finished escape room challenges.</p>
        </x-card>
    </div>

    <!-- Assigned Students Roster Section -->
    <div class="mb-12">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-gray-900 tracking-tight">Assigned Students</h2>
                <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Students enrolled in your software testing class.</p>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700">
                {{ count($students) }} {{ Str::plural('Student', count($students)) }}
            </span>
        </div>

        @if (count($students) === 0)
            <x-card class="py-12 text-center border-dashed border-2 border-gray-200">
                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
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
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Student Name</th>
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Email Address</th>
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Recorded Sessions</th>
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Last Active</th>
                                <th scope="col" class="px-6 py-3.5 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($students as $student)
                                <tr class="hover:bg-gray-50/60 transition">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 font-bold text-xs flex items-center justify-center">
                                                {{ strtoupper(substr($student['name'], 0, 1)) }}
                                            </div>
                                            <span>{{ $student['name'] }}</span>
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
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-100">
                                                Active Learner
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">
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
                                <div class="w-9 h-9 rounded-xl bg-indigo-100 text-indigo-700 font-bold text-sm flex items-center justify-center">
                                    {{ strtoupper(substr($student['name'], 0, 1)) }}
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-gray-900">{{ $student['name'] }}</h3>
                                    <p class="text-xs text-gray-500">{{ $student['email'] }}</p>
                                </div>
                            </div>
                            @if ($student['session_count'] > 0)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-gray-100 text-gray-600 border border-gray-200">
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
                <h2 class="text-xl sm:text-2xl font-bold text-gray-900 tracking-tight">Student Sessions</h2>
                <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Recorded escape room runs across assigned students.</p>
            </div>

            <!-- Simple Alpine.js Status Filter -->
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    @click="activeFilter = 'all'"
                    :class="activeFilter === 'all' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200'"
                    class="px-3 py-1.5 rounded-xl text-xs font-semibold transition">
                    All ({{ count($sessions) }})
                </button>
                <button
                    type="button"
                    @click="activeFilter = 'in_progress'"
                    :class="activeFilter === 'in_progress' ? 'bg-blue-600 text-white shadow-xs' : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200'"
                    class="px-3 py-1.5 rounded-xl text-xs font-semibold transition">
                    In Progress ({{ $overview['in_progress_count'] }})
                </button>
                <button
                    type="button"
                    @click="activeFilter = 'waiting'"
                    :class="activeFilter === 'waiting' ? 'bg-amber-500 text-white shadow-xs' : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200'"
                    class="px-3 py-1.5 rounded-xl text-xs font-semibold transition">
                    Waiting ({{ $overview['waiting_count'] }})
                </button>
                <button
                    type="button"
                    @click="activeFilter = 'finished'"
                    :class="activeFilter === 'finished' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200'"
                    class="px-3 py-1.5 rounded-xl text-xs font-semibold transition">
                    Finished ({{ $overview['finished_count'] }})
                </button>
            </div>
        </div>

        @if (count($sessions) === 0)
            <x-card class="py-12 text-center border-dashed border-2 border-gray-200">
                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
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
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Date</th>
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Student</th>
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Escape Room</th>
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Score</th>
                                <th scope="col" class="px-6 py-3.5 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">Report</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
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
