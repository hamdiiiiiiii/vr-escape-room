<x-app-layout>
    <x-page-heading
        :title="'Start Mission: ' . $room['name']"
        :description="'Time Limit: ' . $room['time_limit_minutes'] . ' minutes &bull; Concept: ' . $room['concept_tag']">
        <x-slot name="actions">
            <a href="{{ route('student.dashboard') }}">
                <x-secondary-button type="button">
                    &larr; Back to Dashboard
                </x-secondary-button>
            </a>
        </x-slot>
    </x-page-heading>

    <x-card title="VR Room Launch Pad" description="Pair your web session with the VR headset keypad.">
        <div class="py-12 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center shadow-xs">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900">{{ $room['name'] }}</h3>
            <p class="text-sm text-gray-500 mt-2 max-w-md mx-auto">
                {{ $room['description'] }}
            </p>

            <div class="mt-6 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm font-medium">
                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Start room screen comes in Phase 4 (6-digit keypad code and 10-minute countdown timer).</span>
            </div>

            <div class="mt-8 flex justify-center">
                <a href="{{ route('student.dashboard') }}">
                    <x-primary-button type="button">
                        Return to Rooms
                    </x-primary-button>
                </a>
            </div>
        </div>
    </x-card>
</x-app-layout>
