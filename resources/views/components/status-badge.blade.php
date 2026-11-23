@props(['status'])

@php
    $normalized = strtolower(trim((string)$status));
    $label = match($normalized) {
        'waiting' => 'Waiting',
        'in_progress', 'in progress', 'progress' => 'In Progress',
        'finished', 'completed' => 'Finished',
        'expired' => 'Expired',
        default => ucfirst($status),
    };

    $classes = match($normalized) {
        'waiting' => 'bg-amber-100 text-amber-700 border-amber-200',
        'in_progress', 'in progress', 'progress' => 'bg-blue-100 text-blue-700 border-blue-200',
        'finished', 'completed' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
        'expired' => 'bg-rose-100 text-rose-700 border-rose-200',
        default => 'bg-gray-100 text-gray-800 border-gray-200',
    };

    $dotClasses = match($normalized) {
        'waiting' => 'bg-amber-500',
        'in_progress', 'in progress', 'progress' => 'bg-blue-500 animate-pulse',
        'finished', 'completed' => 'bg-emerald-500',
        'expired' => 'bg-rose-500',
        default => 'bg-gray-400',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-2.5 py-1 rounded font-mono text-[11px] font-semibold uppercase tracking-wider border {$classes}"]) }}>
    <span class="w-1.5 h-1.5 rounded-full shadow-[0_0_6px_currentColor] {{ $dotClasses }}"></span>
    {{ $label }}
</span>

