<x-app-layout>
    <x-page-heading title="Content Pack" description="The rooms and puzzles students play in VR. Read-only for now.">
        <x-slot name="actions">
            <div class="flex flex-col items-end gap-1.5">
                <button type="button" disabled aria-describedby="soon"
                        class="min-h-[44px] px-5 rounded-lg border border-dashed border-gray-300 text-gray-500 font-semibold text-sm cursor-not-allowed">+ Add puzzle</button>
                <span id="soon" class="font-mono text-xs text-gray-500">editing forms arrive in a later release</span>
            </div>
        </x-slot>
    </x-page-heading>

    @if (count($rooms) === 0)
        <x-card class="py-12 text-center border-dashed border-2 border-gray-200">
            <p class="text-sm text-gray-500">No rooms in the content pack yet.</p>
        </x-card>
    @else
        <div x-data="{ sel: {{ $rooms[0]['id'] }} }" class="flex flex-wrap gap-6 items-start">
            <div role="tablist" aria-label="Rooms" class="flex-[1_1_280px] flex flex-col gap-3">
                @foreach ($rooms as $room)
                    <button type="button" role="tab" @click="sel = {{ $room['id'] }}" :aria-selected="(sel === {{ $room['id'] }}).toString()"
                            :class="sel === {{ $room['id'] }} ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 bg-chamber-800/90'"
                            class="text-left p-4 rounded-xl border-2 flex flex-col gap-1.5 transition">
                        <span class="font-mono text-xs text-gray-500">ROOM {{ str_pad($room['id'], 2, '0', STR_PAD_LEFT) }} &middot; {{ $room['puzzle_count'] }} puzzles &middot; {{ $room['time_limit_minutes'] }} min</span>
                        <span class="font-display text-xl font-bold text-gray-900">{{ $room['name'] }}</span>
                    </button>
                @endforeach
            </div>

            <div class="flex-[3_1_560px] min-w-0">
                @foreach ($rooms as $room)
                    <section x-show="sel === {{ $room['id'] }}" @if (! $loop->first) x-cloak @endif role="tabpanel" aria-label="{{ $room['name'] }}"
                             class="rounded-2xl border-2 border-gray-300 bg-chamber-800 overflow-hidden shadow-[0_0_60px_#3fe0c514]">
                        <div class="hud-hazard h-2.5" aria-hidden="true"></div>
                        <div class="p-6 sm:p-7 flex flex-col gap-5">
                            <div class="flex flex-wrap justify-between gap-3">
                                <div>
                                    <p class="hud-eyebrow mb-1.5">ROOM {{ str_pad($room['id'], 2, '0', STR_PAD_LEFT) }}</p>
                                    <h2 class="font-display text-2xl sm:text-3xl font-bold text-gray-900 uppercase">{{ $room['name'] }}</h2>
                                    <p class="text-sm text-gray-600 mt-2 max-w-xl leading-relaxed">{{ $room['description'] }}</p>
                                </div>
                                <div class="flex gap-2.5 items-start font-mono text-xs">
                                    <span class="px-2.5 py-1.5 rounded-md border border-amber-500 text-amber-500">{{ $room['time_limit_minutes'] }}:00 timer</span>
                                    <span class="px-2.5 py-1.5 rounded-md border border-gray-300 text-gray-600">{{ $room['max_hints'] }} hints / puzzle</span>
                                </div>
                            </div>

                            <ol class="space-y-2.5 list-none p-0 m-0">
                                @foreach ($room['puzzles'] as $puzzle)
                                    <li class="flex flex-wrap items-center gap-3.5 p-3.5 rounded-lg bg-chamber-950 border border-gray-100">
                                        <span class="font-display text-2xl font-bold text-amber-500 w-9">{{ $puzzle['n'] }}</span>
                                        <span class="flex-1 min-w-[220px]">
                                            <span class="block font-semibold text-gray-900">{{ $puzzle['title'] }}</span>
                                            <span class="block text-sm text-gray-500 leading-snug">{{ $puzzle['prompt'] }}</span>
                                            <span class="block font-mono text-[11px] text-gray-500 mt-1">{{ $puzzle['concept'] }} &middot; {{ $puzzle['hints'] }} hints</span>
                                        </span>
                                        <span class="font-mono text-[11px] font-semibold px-2 py-1 rounded border uppercase {{ $puzzle['type'] === 'assembly' ? 'border-amber-500 text-amber-500' : 'border-indigo-500 text-indigo-500' }}">{{ $puzzle['type'] }}</span>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
    @endif
</x-app-layout>
