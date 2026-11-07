<x-app-layout>
    <x-page-heading
        title="Instructor Dashboard"
        description="Monitor student escape room performance, review testing analytics, and manage classroom VR sessions.">
        <x-slot name="actions">
            <x-primary-button type="button">
                Export Gradebook
            </x-primary-button>
        </x-slot>
    </x-page-heading>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Live Class Overview -->
        <x-card class="border-l-4 border-l-purple-600">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-purple-600 uppercase tracking-wider">Active Headsets</p>
                    <h3 class="text-2xl font-black text-gray-900 mt-1">4 Students</h3>
                </div>
                <x-status-badge status="in_progress" />
            </div>
            <p class="text-xs text-gray-500 mt-3">Currently solving Software Testing bugs.</p>
        </x-card>

        <x-card class="border-l-4 border-l-amber-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-amber-600 uppercase tracking-wider">Pending Start</p>
                    <h3 class="text-2xl font-black text-gray-900 mt-1">1 Headset</h3>
                </div>
                <x-status-badge status="waiting" />
            </div>
            <p class="text-xs text-gray-500 mt-3">Awaiting VR keypad code entry.</p>
        </x-card>

        <x-card class="border-l-4 border-l-emerald-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Completed Today</p>
                    <h3 class="text-2xl font-black text-gray-900 mt-1">12 Sessions</h3>
                </div>
                <x-status-badge status="finished" />
            </div>
            <p class="text-xs text-gray-500 mt-3">Average score: 86.4%.</p>
        </x-card>
    </div>

    <!-- Instructor Workspace placeholder -->
    <x-card title="Instructor Controls" description="Classroom session overview, analytics, and student evaluation tools.">
        <div class="py-12 text-center">
            <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
            </div>
            <h3 class="text-base font-bold text-gray-900">Instructor Management Module</h3>
            <p class="text-sm text-gray-500 mt-1 max-w-lg mx-auto">
                Member 2 fills this in — Real-time classroom monitoring, session logs, student testing mistake breakdowns, and AI feedback reports will live here.
            </p>
            <div class="mt-6 flex items-center justify-center gap-3">
                <x-primary-button type="button">
                    View Class Reports
                </x-primary-button>
                <x-secondary-button type="button">
                    Room Configurations
                </x-secondary-button>
            </div>
        </div>
    </x-card>
</x-app-layout>
