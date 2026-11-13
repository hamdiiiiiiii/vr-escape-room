<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center min-h-[44px] px-5 py-2.5 bg-transparent border border-gray-300 hover:border-indigo-500 hover:bg-indigo-50 rounded-lg font-display font-bold text-sm uppercase tracking-[.08em] text-gray-700 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-chamber-950 transition duration-150 disabled:opacity-50 disabled:cursor-not-allowed']) }}>
    {{ $slot }}
</button>
