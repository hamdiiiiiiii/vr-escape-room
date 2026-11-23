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

    {{-- AI puzzle generator --}}
    <section id="ai-generator" class="mt-10" aria-label="AI puzzle generator">
        <p class="hud-eyebrow mb-1.5">AI_GENERATOR</p>
        <h2 class="font-display text-2xl font-bold text-gray-900 uppercase">AI-written puzzles</h2>
        <p class="text-sm text-gray-600 mt-2 max-w-2xl leading-relaxed">
            Ask the AI for a new puzzle on a concept students struggle with. It only gets the concept and the mistakes students made, never names.
            Every puzzle starts as <strong>pending</strong>. Students only get the ones you approve.
        </p>

        @if (session('ai_ok'))
            <p role="status" class="mt-4 px-4 py-3 rounded-lg border border-emerald-500 text-emerald-500 text-sm">{{ session('ai_ok') }}</p>
        @endif
        @if (session('ai_error'))
            <p role="alert" class="mt-4 px-4 py-3 rounded-lg border border-rose-500 text-rose-500 text-sm">{{ session('ai_error') }}</p>
        @endif

        <x-card class="mt-4 p-5">
            @if (! $aiReady)
                <p class="text-sm text-amber-500 mb-3">The AI is not set up yet (no API key in the server settings). You can still look around; generating is switched off.</p>
            @endif
            <form method="POST" action="{{ route('instructor.generated-puzzles.store') }}" class="flex flex-wrap items-end gap-4">
                @csrf
                <label class="flex flex-col gap-1 text-sm text-gray-600">Concept
                    <select name="concept" required class="rounded-lg border-gray-300 bg-chamber-950 text-gray-900 text-sm">
                        @foreach ($concepts as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach
                    </select>
                </label>
                <label class="flex flex-col gap-1 text-sm text-gray-600">Type
                    <select name="type" required class="rounded-lg border-gray-300 bg-chamber-950 text-gray-900 text-sm">
                        <option value="observation">observation (find the bug)</option>
                        <option value="assembly">assembly (build the test)</option>
                    </select>
                </label>
                <label class="flex flex-col gap-1 text-sm text-gray-600">Difficulty
                    <select name="difficulty" required class="rounded-lg border-gray-300 bg-chamber-950 text-gray-900 text-sm">
                        <option value="same">same level</option>
                        <option value="easier">easier</option>
                    </select>
                </label>
                <label class="flex flex-col gap-1 text-sm text-gray-600">For
                    <select name="student_id" class="rounded-lg border-gray-300 bg-chamber-950 text-gray-900 text-sm">
                        <option value="">whole class</option>
                        @foreach ($students as $st)<option value="{{ $st->id }}">{{ $st->name }}</option>@endforeach
                    </select>
                </label>
                <x-primary-button :disabled="! $aiReady">Generate puzzle</x-primary-button>
            </form>
        </x-card>

        <div class="mt-5 space-y-4">
            @forelse ($generated as $g)
                @php $c = $g->content; @endphp
                <x-card class="p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="font-mono text-xs text-gray-500">
                            {{ $g->concept_tag }} &middot; {{ $g->type }} &middot; {{ $g->difficulty }} &middot;
                            for {{ $g->student?->name ?? 'whole class' }}
                        </p>
                        <span class="font-mono text-[11px] font-semibold px-2 py-1 rounded border uppercase
                            {{ $g->status === 'approved' ? 'border-emerald-500 text-emerald-500' : ($g->status === 'rejected' ? 'border-rose-500 text-rose-500' : 'border-amber-500 text-amber-500') }}">{{ $g->status }}</span>
                    </div>
                    <p class="mt-3 text-gray-900">{{ $c['prompt'] }}</p>
                    @if ($g->type === 'observation')
                        <pre class="mt-3 p-3 rounded-lg bg-chamber-950 border border-gray-100 text-xs font-mono text-gray-700 overflow-x-auto">@foreach ($c['data']['codeSnippet'] as $l){{ $l['lineNumber'] }}  {{ $l['text'] }}
@endforeach</pre>
                        <p class="mt-2 text-sm text-gray-600">Answer: line <strong>{{ $c['answerKey']['buggyLineNumber'] }}</strong></p>
                    @else
                        <p class="mt-3 text-sm text-gray-600">Inputs: {{ implode(', ', $c['data']['options']['inputs']) }} &middot; Outputs: {{ implode(', ', $c['data']['options']['expectedOutputs']) }}</p>
                        <p class="mt-1 text-sm text-gray-600">Answer: input <strong>{{ $c['answerKey']['input'] }}</strong> gives <strong>{{ $c['answerKey']['expectedOutput'] }}</strong></p>
                    @endif
                    <ul class="mt-2 text-sm text-gray-500 list-disc pl-5">
                        @foreach ($c['hints'] as $h)<li>{{ $h }}</li>@endforeach
                    </ul>
                    <p class="mt-2 text-sm text-gray-500">{{ $c['explanation'] }}</p>
                    @if ($g->status === 'pending')
                        <div class="mt-4 flex gap-3">
                            <form method="POST" action="{{ route('instructor.generated-puzzles.approve', $g->id) }}">@csrf<x-primary-button>Approve</x-primary-button></form>
                            <form method="POST" action="{{ route('instructor.generated-puzzles.reject', $g->id) }}">@csrf<x-secondary-button type="submit">Reject</x-secondary-button></form>
                        </div>
                    @endif
                </x-card>
            @empty
                <x-card class="py-10 text-center border-dashed border-2 border-gray-200">
                    <p class="text-sm text-gray-500">No AI puzzles yet.</p>
                </x-card>
            @endforelse
        </div>
    </section>
</x-app-layout>
