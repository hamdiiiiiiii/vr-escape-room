@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-mono text-sm text-emerald-500']) }}>
        &gt; {{ $status }}
    </div>
@endif
