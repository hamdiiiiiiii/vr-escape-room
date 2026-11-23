<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center min-h-[44px] px-5 py-2.5 bg-amber-500 hover:bg-amber-400 hover:-translate-y-px border border-transparent rounded-lg font-display font-bold text-sm uppercase tracking-[.08em] text-chamber-950 shadow-[0_0_28px_#ff8a3d59] hover:shadow-[0_0_44px_#ff8a3d99] focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-chamber-950 transition duration-150 disabled:opacity-50 disabled:cursor-not-allowed']) }}>
    {{ $slot }}
</button>
