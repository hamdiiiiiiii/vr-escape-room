<x-app-layout>
    <x-page-heading
        title="Session Performance Report"
        description="Detailed test breakdown, code coverage, bug detection accuracy, and evaluation score.">
        <x-slot name="actions">
            <a href="{{ route('dashboard') }}" class="w-full sm:w-auto">
                <x-secondary-button type="button" class="w-full sm:w-auto">
                    &larr; Back to Dashboard
                </x-secondary-button>
            </a>
            <x-primary-button type="button" class="w-full sm:w-auto">
                Download PDF
            </x-primary-button>
        </x-slot>
    </x-page-heading>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <x-card>
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Session Identifier</p>
            <p class="text-2xl font-black text-gray-900 mt-1">#{{ $reportId }}</p>
            <p class="text-xs text-gray-500 mt-2">VR Room: Boundary Testing</p>
        </x-card>

        <x-card>
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Evaluation Status</p>
            <div class="mt-2">
                <x-status-badge status="finished" />
            </div>
            <p class="text-xs text-gray-500 mt-2">Evaluation completed successfully.</p>
        </x-card>

        <x-card>
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Overall Score</p>
            <p class="text-2xl font-black text-indigo-600 mt-1">88 / 100</p>
            <p class="text-xs text-emerald-600 font-semibold mt-2">&check; Passed Software Testing Criteria</p>
        </x-card>
    </div>

    <x-card title="Session Report Placeholder" description="Detailed evaluation report generated after completing a VR escape room challenge.">
        <div class="py-12 text-center">
            <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <h3 class="text-base font-bold text-gray-900">Report Details (#{{ $reportId }})</h3>
            <p class="text-sm text-gray-500 mt-1 max-w-lg mx-auto">
                Member 2 fills this in — Bug analysis, time-to-fix metrics, test coverage charts, and AI tutor feedback will be displayed on this report page.
            </p>
        </div>
    </x-card>
</x-app-layout>

