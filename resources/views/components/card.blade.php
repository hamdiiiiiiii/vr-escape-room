@props(['title' => null, 'description' => null])

<div {{ $attributes->merge(['class' => 'hud-panel p-5 sm:p-6']) }}>
    @if ($title || $description)
        <div class="mb-4">
            @if ($title)
                <h3 class="font-display text-lg font-bold text-gray-900 uppercase tracking-[.02em]">{{ $title }}</h3>
            @endif
            @if ($description)
                <p class="text-sm text-gray-500 mt-1">{{ $description }}</p>
            @endif
        </div>
    @endif

    {{ $slot }}
</div>
