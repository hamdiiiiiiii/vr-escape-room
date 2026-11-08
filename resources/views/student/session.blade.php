<x-app-layout>
    <x-page-heading
        :title="$session['room_name']"
        description="Session code screen (placeholder, Phase 4 part B builds the real screen).">
        <x-slot name="actions">
            <a href="{{ route('student.dashboard') }}">
                <x-secondary-button type="button">
                    &larr; Back to Dashboard
                </x-secondary-button>
            </a>
        </x-slot>
    </x-page-heading>

    <x-card title="Your session">
        <dl class="space-y-3 text-sm">
            <div class="flex items-center justify-between">
                <dt class="text-gray-500">Session</dt>
                <dd class="font-semibold text-gray-900">#{{ $session['id'] }}</dd>
            </div>
            <div class="flex items-center justify-between">
                <dt class="text-gray-500">Code</dt>
                <dd class="font-mono text-2xl font-black tracking-widest text-indigo-600">{{ $session['code'] }}</dd>
            </div>
            <div class="flex items-center justify-between">
                <dt class="text-gray-500">Status</dt>
                <dd><x-status-badge :status="$session['status']" /></dd>
            </div>
            <div class="flex items-center justify-between">
                <dt class="text-gray-500">Expires at</dt>
                <dd class="font-semibold text-gray-900">{{ \Illuminate\Support\Carbon::parse($session['expires_at'])->format('H:i:s') }}</dd>
            </div>
        </dl>
    </x-card>
</x-app-layout>
