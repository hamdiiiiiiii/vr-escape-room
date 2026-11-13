@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'bg-chamber-950 border-gray-200 text-gray-900 font-mono placeholder:text-gray-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-lg text-sm py-2.5 px-3 disabled:opacity-60']) }}>
