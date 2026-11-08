<nav x-data="{ open: false }" class="bg-white border-b border-gray-200/80 sticky top-0 z-50 shadow-xs">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Left: Logo & Brand + Desktop Navigation -->
            <div class="flex items-center gap-8">
                <!-- Logo -->
                <a href="{{ route('dashboard') }}" class="flex items-center transition hover:opacity-95">
                    <x-application-logo />
                </a>

                <!-- Desktop Role Links -->
                <div class="hidden sm:flex sm:items-center sm:space-x-4">
                    @if (Auth::user()?->isInstructor())
                        <a href="{{ route('instructor.dashboard') }}"
                           class="px-3.5 py-2 rounded-xl text-sm font-semibold transition {{ request()->routeIs('instructor.dashboard') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                            {{ __('Instructor Dashboard') }}
                        </a>
                    @else
                        <a href="{{ route('student.dashboard') }}"
                           class="px-3.5 py-2 rounded-xl text-sm font-semibold transition {{ request()->routeIs('student.dashboard') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50' }}">
                            {{ __('Student Dashboard') }}
                        </a>
                    @endif
                </div>
            </div>

            <!-- Right: User Info & Log out Button (Desktop) -->
            <div class="hidden sm:flex sm:items-center sm:gap-4">
                <div class="flex items-center gap-2.5 px-3 py-1.5 rounded-xl bg-gray-50 border border-gray-100">
                    <div class="w-7 h-7 rounded-lg bg-indigo-100 text-indigo-700 font-bold text-xs flex items-center justify-center">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                    <div class="text-left">
                        <div class="text-xs font-semibold text-gray-800 leading-tight">{{ Auth::user()->name }}</div>
                        <span class="inline-block text-[10px] font-medium tracking-wide uppercase {{ Auth::user()->isInstructor() ? 'text-purple-600' : 'text-indigo-600' }}">
                            {{ Auth::user()->role }}
                        </span>
                    </div>
                </div>

                <!-- Log out Form -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center min-h-[44px] px-3.5 py-2 text-sm font-medium text-gray-600 hover:text-red-600 hover:bg-red-50 rounded-xl transition">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        {{ __('Log Out') }}
                    </button>
                </form>
            </div>

            <!-- Mobile Hamburger Button -->
            <div class="flex items-center sm:hidden">
                <button @click="open = ! open"
                        type="button"
                        class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] p-2 rounded-xl text-gray-500 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition"
                        aria-label="Toggle navigation menu">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Drawer Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden border-t border-gray-100 bg-white px-4 pt-3 pb-5 space-y-3">
        <!-- User Pill -->
        <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 border border-gray-100">
            <div class="w-9 h-9 rounded-xl bg-indigo-600 text-white font-bold text-sm flex items-center justify-center shadow-xs">
                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
            </div>
            <div>
                <div class="text-sm font-bold text-gray-900 leading-tight">{{ Auth::user()->name }}</div>
                <div class="text-xs text-gray-500">{{ Auth::user()->email }}</div>
                <span class="inline-block mt-0.5 px-2 py-0.5 rounded-md text-[10px] font-semibold uppercase tracking-wider {{ Auth::user()->isInstructor() ? 'bg-purple-100 text-purple-700' : 'bg-indigo-100 text-indigo-700' }}">
                    {{ Auth::user()->role }}
                </span>
            </div>
        </div>

        <!-- Mobile Navigation Links -->
        <div class="space-y-1">
            @if (Auth::user()?->isInstructor())
                <a href="{{ route('instructor.dashboard') }}"
                   class="flex items-center min-h-[44px] px-3.5 py-2.5 rounded-xl text-base font-semibold transition {{ request()->routeIs('instructor.dashboard') ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-gray-700 hover:bg-gray-50' }}">
                    {{ __('Instructor Dashboard') }}
                </a>
            @else
                <a href="{{ route('student.dashboard') }}"
                   class="flex items-center min-h-[44px] px-3.5 py-2.5 rounded-xl text-base font-semibold transition {{ request()->routeIs('student.dashboard') ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-gray-700 hover:bg-gray-50' }}">
                    {{ __('Student Dashboard') }}
                </a>
            @endif
        </div>

        <!-- Mobile Log out Form -->
        <div class="pt-2 border-t border-gray-100">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="w-full flex items-center justify-center min-h-[44px] gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-red-600 bg-red-50 hover:bg-red-100 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    {{ __('Log Out') }}
                </button>
            </form>
        </div>
    </div>
</nav>
