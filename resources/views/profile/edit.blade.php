<x-app-layout>
    <x-page-heading
        title="Profile Settings"
        description="Manage your account profile information and security password.">
        <x-slot name="actions">
            <a href="{{ route('dashboard') }}" class="w-full sm:w-auto">
                <x-secondary-button type="button" class="w-full sm:w-auto">
                    &larr; Back to Dashboard
                </x-secondary-button>
            </a>
        </x-slot>
    </x-page-heading>

    <div class="space-y-6 max-w-4xl">
        <x-card>
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </x-card>

        <x-card>
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </x-card>
    </div>
</x-app-layout>
