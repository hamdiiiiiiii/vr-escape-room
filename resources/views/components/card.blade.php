@props(['title' => null, 'description' => null])

<div {{ $attributes->merge(['class' => 'bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8']) }}>
    @if ($title || $description)
        <div class="mb-4">
            @if ($title)
                <h3 class="text-lg font-bold text-gray-900 tracking-tight">{{ $title }}</h3>
            @endif
            @if ($description)
                <p class="text-sm text-gray-500 mt-1">{{ $description }}</p>
            @endif
        </div>
    @endif

    {{ $slot }}
</div>
