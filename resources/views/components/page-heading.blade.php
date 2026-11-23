@props(['title', 'description' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 sm:mb-8']) }}>
    <div>
        <p class="hud-eyebrow mb-1.5">// control_room</p>
        <h1 class="font-display text-2xl sm:text-3xl font-bold text-gray-900 uppercase tracking-[.01em]">{{ $title }}</h1>
        @if ($description)
            <p class="text-sm sm:text-base text-gray-500 mt-1.5 max-w-3xl">{{ $description }}</p>
        @endif
    </div>

    @if (isset($actions))
        <div class="flex flex-wrap items-center gap-3">
            {{ $actions }}
        </div>
    @endif
</div>
