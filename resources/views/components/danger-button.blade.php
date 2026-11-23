<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center min-h-[44px] px-5 py-2.5 bg-rose-500 hover:bg-rose-400 border border-transparent rounded-lg font-display font-bold text-sm uppercase tracking-[.08em] text-chamber-950 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 focus:ring-offset-chamber-950 transition duration-150']) }}>
    {{ $slot }}
</button>
