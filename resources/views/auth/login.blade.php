<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <div x-data="{ show: false }" class="relative">
                <x-text-input id="password" class="block mt-1 w-full pr-12"
                                x-bind:type="show ? 'text' : 'password'"
                                type="password"
                                name="password"
                                required autocomplete="current-password" />
                <button type="button" @click="show = !show" :aria-label="show ? 'Hide password' : 'Show password'" :aria-pressed="show.toString()"
                        class="absolute right-1 top-[7px] w-11 h-11 grid place-items-center text-gray-500 hover:text-gray-900 rounded-md">
                    <svg x-show="!show" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg x-show="show" x-cloak class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18M10.6 10.6a2 2 0 002.8 2.8M9.9 5.2A10 10 0 0112 5c5.5 0 9 7 9 7a16 16 0 01-3.2 4M6.6 6.6C4.2 8.2 3 12 3 12s3.5 7 9 7c1.7 0 3.2-.5 4.4-1.2"/></svg>
                </button>
            </div>

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="border-gray-300 text-indigo-500 rounded-lg focus:ring-indigo-500 focus:ring-offset-0" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-6">
            @if (Route::has('password.request'))
                <a class="underline decoration-indigo-500/40 underline-offset-4 text-sm text-indigo-500 hover:text-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @else
                <div></div>
            @endif

            <x-primary-button class="w-full sm:w-auto">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
