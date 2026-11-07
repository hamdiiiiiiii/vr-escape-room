<x-app-layout>
    <x-page-heading
        title="Student Dashboard"
        description="Welcome to your BugHunt VR testing command center! Pick a testing mission or check your test scores below.">
        <x-slot name="actions">
            <x-primary-button type="button">
                Explore Rooms
            </x-primary-button>
        </x-slot>
    </x-page-heading>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Quick stats preview card -->
        <x-card class="border-l-4 border-l-indigo-600">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Next Mission</p>
                    <h3 class="text-lg font-bold text-gray-900 mt-1">Boundary Testing</h3>
                </div>
                <x-status-badge status="waiting" />
            </div>
            <p class="text-xs text-gray-500 mt-3">Ready to begin your VR escape run.</p>
        </x-card>

        <x-card class="border-l-4 border-l-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-blue-600 uppercase tracking-wider">Active Run</p>
                    <h3 class="text-lg font-bold text-gray-900 mt-1">Room 201</h3>
                </div>
                <x-status-badge status="in_progress" />
            </div>
            <p class="text-xs text-gray-500 mt-3">Connected to headset in Lab A.</p>
        </x-card>

        <x-card class="border-l-4 border-l-emerald-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Latest Score</p>
                    <h3 class="text-lg font-bold text-gray-900 mt-1">92 / 100</h3>
                </div>
                <x-status-badge status="finished" />
            </div>
            <p class="text-xs text-gray-500 mt-3">Equivalence Partitioning completed.</p>
        </x-card>
    </div>

    <!-- Main placeholder card -->
    <x-card title="Student Workspace" description="Interactive escape room launcher and past session history.">
        <div class="py-10 text-center">
            <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
            </div>
            <h3 class="text-base font-bold text-gray-900">Student Dashboard Ready</h3>
            <p class="text-sm text-gray-500 mt-1 max-w-md mx-auto">
                Member 2 fills this in — Room selection cards, 6-digit VR code launcher, and past test session history will be displayed here.
            </p>
            <div class="mt-6 flex items-center justify-center gap-3">
                <x-primary-button type="button">
                    Start Test Mission
                </x-primary-button>
                <x-secondary-button type="button">
                    View Sample Report
                </x-secondary-button>
            </div>
        </div>
    </x-card>
</x-app-layout>
