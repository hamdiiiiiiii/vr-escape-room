<?php

namespace App\Services;

use App\Models\User;

/**
 * Everything about student avatars in one place: the catalogue, validation lists, the public player name,
 * default avatars, and the payload sent to Unity. The look is stored as ids from config/avatar.php, so the
 * website and Unity can both build it. (Squads live in SquadService.)
 */
class AvatarService
{
    /** Keys of a stored look that hold a catalogue id. */
    private const LOOK_KEYS = ['head', 'skin_tone', 'hair_color', 'outfit', 'headgear', 'gear', 'held', 'suit_color', 'team_color'];

    /** The catalogue exactly as Unity receives it from GET /api/avatar/options. */
    public function catalog(): array
    {
        $c = config('avatar');

        return [
            'version' => 2,
            'heads' => array_map(fn ($h) => ['id' => $h['id'], 'gender' => $h['gender'], 'hairStyle' => $h['style']], $c['heads']),
            'skinTones' => $this->map($c['skin_tones']),
            'hairColors' => $this->map($c['hair_colors']),
            'outfits' => $c['outfits'],
            'headgear' => $c['headgear'],
            'gear' => $c['gear'],
            'held' => $c['held'],
            'suitColors' => $this->map($c['suit_colors']),
            'teamColors' => $this->map($c['team_colors']),
            'nameWords' => $c['name_words'],
            'defaults' => $c['defaults'],
            'squad' => ['maxPlayers' => $c['squad']['max_players'], 'minForMultiplayer' => $c['squad']['min_for_multiplayer']],
        ];
    }

    /** @return list<array{id: string, hex: string}> */
    private function map(array $colors): array
    {
        $out = [];
        foreach ($colors as $id => $hex) {
            $out[] = ['id' => $id, 'hex' => $hex];
        }

        return $out;
    }

    /** Lists the validation rules check against. */
    public function ids(string $group): array
    {
        $c = config('avatar');

        return match ($group) {
            'heads' => array_column($c['heads'], 'id'),
            'outfits' => array_column($c['outfits'], 'id'),
            'headgear' => array_column($c['headgear'], 'id'),
            'gear' => array_column($c['gear'], 'id'),
            'held' => array_column($c['held'], 'id'),
            'skin_tones' => array_keys($c['skin_tones']),
            'hair_colors' => array_keys($c['hair_colors']),
            'suit_colors' => array_keys($c['suit_colors']),
            'team_colors' => array_keys($c['team_colors']),
            'name_words' => $c['name_words'],
            default => [],
        };
    }

    /** Which catalogue list a look key is checked against. */
    private function groupOf(string $key): string
    {
        return match ($key) {
            'head' => 'heads', 'skin_tone' => 'skin_tones', 'hair_color' => 'hair_colors', 'outfit' => 'outfits',
            'headgear' => 'headgear', 'gear' => 'gear', 'held' => 'held', 'suit_color' => 'suit_colors', 'team_color' => 'team_colors',
        };
    }

    /** Builds the public name: "Raven" + "Nova" + 77 = "RavenNova_77". Only approved words and a number. */
    public function callsign(string $first, ?string $second = null, ?int $number = null): string
    {
        $name = $first.($second ?? '');

        return $number !== null ? $name.'_'.$number : $name;
    }

    /** Clean the validated form data into the stored look (ids only). */
    public function normalise(array $data): array
    {
        $look = [];
        foreach (self::LOOK_KEYS as $key) {
            $look[$key] = $data[$key];
        }
        $look['name'] = [
            'first' => $data['name_first'],
            'second' => ($data['name_second'] ?? '') === '' ? null : $data['name_second'],
            'number' => ($data['name_number'] ?? '') === '' ? null : (int) $data['name_number'],
        ];

        return $look;
    }

    /**
     * A complete, valid look from whatever is stored. Keys that are missing (looks saved before an option existed)
     * or no longer in the catalogue fall back to the default, so old accounts never break.
     */
    public function completeLook(?array $stored): array
    {
        $defaults = config('avatar.defaults');
        $stored = $stored ?? [];
        $look = [];
        foreach (self::LOOK_KEYS as $key) {
            $value = $stored[$key] ?? null;
            $look[$key] = is_string($value) && in_array($value, $this->ids($this->groupOf($key)), true) ? $value : $defaults[$key];
        }

        $name = is_array($stored['name'] ?? null) ? $stored['name'] : [];
        $words = $this->ids('name_words');
        $first = $name['first'] ?? null;
        $second = $name['second'] ?? null;
        $number = $name['number'] ?? null;
        $look['name'] = [
            'first' => in_array($first, $words, true) ? $first : $words[0],
            'second' => in_array($second, $words, true) ? $second : null,
            'number' => is_int($number) && $number >= 1 && $number <= 99 ? $number : null,
        ];

        return $look;
    }

    public function save(User $user, array $look): User
    {
        $look = $this->completeLook($look);
        $name = $look['name'];
        $user->forceFill([
            'avatar' => $look,
            'callsign' => $this->callsign($name['first'], $name['second'], $name['number']),
        ])->save();

        return $user;
    }

    public function callsignTaken(string $callsign, ?int $exceptUserId): bool
    {
        return User::where('callsign', $callsign)
            ->when($exceptUserId, fn ($q) => $q->where('id', '!=', $exceptUserId))
            ->exists();
    }

    /** A valid starting avatar for a student who has not customised yet. Stable per user, unique callsign. */
    public function defaultFor(User $user): array
    {
        $c = config('avatar');
        $words = $c['name_words'];
        $seed = $user->id * 7919;
        $look = $c['defaults'];
        $look['head'] = $c['heads'][$user->id % count($c['heads'])]['id'];
        $tones = array_keys($c['skin_tones']);
        $look['skin_tone'] = $tones[$user->id % count($tones)];
        $naturalHair = ['black', 'brown', 'chestnut', 'ginger', 'blonde', 'grey'];
        $look['hair_color'] = $naturalHair[$user->id % count($naturalHair)];

        for ($i = 0; $i < 200; $i++) {
            $first = $words[($seed + $i * 5) % count($words)];
            $number = 10 + (($seed + $i * 13) % 90);
            $callsign = $this->callsign($first, null, $number);
            if (! $this->callsignTaken($callsign, $user->id)) {
                $look['name'] = ['first' => $first, 'second' => null, 'number' => $number];

                return $look;
            }
        }

        // practically unreachable; keep a guaranteed unique name
        $look['name'] = ['first' => $words[0], 'second' => null, 'number' => null];

        return $look;
    }

    /** Make sure the student has a saved avatar and callsign, then return the (complete) look. */
    public function ensure(User $user): array
    {
        if (is_array($user->avatar) && $user->callsign) {
            return $this->completeLook($user->avatar);
        }

        $look = $this->defaultFor($user);
        $this->save($user, $look);

        return $look;
    }

    /** The look as Unity names its fields (camelCase). */
    public function unityLook(array $look): array
    {
        $look = $this->completeLook($look);

        return [
            'head' => $look['head'],
            'skinTone' => $look['skin_tone'],
            'hairColor' => $look['hair_color'],
            'outfit' => $look['outfit'],
            'headgear' => $look['headgear'],
            'gear' => $look['gear'],
            'held' => $look['held'],
            'suitColor' => $look['suit_color'],
            'teamColor' => $look['team_color'],
        ];
    }

    /** What Unity gets for one player: public name + look. Never the real name or email. */
    public function playerPayload(User $user): array
    {
        $look = $this->ensure($user);

        return ['callsign' => $user->callsign, 'avatar' => $this->unityLook($look)];
    }

    /** Public name for a user without saving anything (used for squad mates who never customised). */
    public function publicView(User $user): array
    {
        if (is_array($user->avatar) && $user->callsign) {
            return ['callsign' => $user->callsign, 'look' => $this->completeLook($user->avatar)];
        }
        $look = $this->defaultFor($user);
        $n = $look['name'];

        return ['callsign' => $this->callsign($n['first'], $n['second'], $n['number']), 'look' => $look];
    }
}
