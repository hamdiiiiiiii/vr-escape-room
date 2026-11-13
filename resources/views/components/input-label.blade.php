@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-mono text-[11px] font-semibold uppercase tracking-[.14em] text-gray-500']) }}>
    {{ $value ?? $slot }}
</label>
