<x-app-layout>
    <!-- Welcome Header -->
    <x-page-heading
        :title="'Welcome, ' . (Auth::user()?->name ?? 'Student') . '!'"
        description="Pick an escape room mission to start hunting software bugs in VR, or review your test scores below.">
    </x-page-heading>

    <!-- Available Escape Rooms Section -->
    <div class="mb-12">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-5">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-gray-900 tracking-tight">Available Escape Rooms</h2>
                <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Select a challenge to generate your 6-digit VR headset access code.</p>
            </div>
            <span class="inline-flex self-start sm:self-auto items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700">
                {{ count($rooms) }} Rooms Available
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach ($rooms as $room)
                <x-card class="flex flex-col justify-between border-t-4 border-t-indigo-600 hover:shadow-md transition">
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                {{ $room['concept_tag'] }}
                            </span>
                            <span class="inline-flex items-center text-xs font-medium text-gray-500 gap-1">
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ $room['time_limit_minutes'] }} mins
                            </span>
                        </div>

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
                <h2 class="text-xl sm:text-2xl font-bold text-gray-900 tracking-tight">My Past Sessions</h2>
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
                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center shadow-xs">
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
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Date</th>
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Room</th>
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Score</th>
                                <th scope="col" class="px-6 py-3.5 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">Report</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
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
