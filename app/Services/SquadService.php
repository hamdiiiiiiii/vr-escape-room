<?php

namespace App\Services;

use App\Models\Squad;
use App\Models\SquadMember;
use App\Models\User;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Squads: small teams of classmates, from 2 to 4 players (config avatar.squad.max_players).
 * A student who plays alone has no squad. Only the leader adds or removes players, and only classmates
 * of the same instructor can be added. Anyone can leave. A squad with a single player left is removed.
 * All failures throw DomainException with a message that is safe to show to a student.
 */
class SquadService
{
    public function __construct(private AvatarService $avatars)
    {
    }

    public function max(): int
    {
        return (int) config('avatar.squad.max_players');
    }

    public function squadOf(User $user): ?Squad
    {
        $membership = SquadMember::where('user_id', $user->id)->first();

        return $membership ? Squad::find($membership->squad_id) : null;
    }

    /** The members of the user's squad, leader first. Without a squad that is just the user. */
    public function membersOf(User $user): Collection
    {
        $squad = $this->squadOf($user);
        if ($squad === null) {
            return collect([$user]);
        }

        $users = User::whereIn('id', $squad->memberships()->pluck('user_id'))->get()->keyBy('id');
        $ordered = $squad->memberships()->pluck('user_id')->map(fn ($id) => $users->get($id))->filter();

        return $ordered->sortByDesc(fn (User $u) => $u->id === $squad->leader_id)->values();
    }

    public function isLeader(User $user): bool
    {
        $squad = $this->squadOf($user);

        return $squad === null || $squad->leader_id === $user->id;
    }

    /** Classmates the user could add: same instructor, not already in a squad, not the user. */
    public function candidates(User $user): Collection
    {
        if (! $user->instructor_id) {
            return collect();
        }

        return User::where('role', 'student')
            ->where('instructor_id', $user->instructor_id)
            ->where('id', '!=', $user->id)
            ->whereNotIn('id', SquadMember::pluck('user_id'))
            ->orderBy('id')
            ->limit(30)
            ->get();
    }

    public function add(User $leader, User $mate): void
    {
        DB::transaction(function () use ($leader, $mate) {
            $squad = $this->squadOf($leader);

            if ($squad !== null && $squad->leader_id !== $leader->id) {
                throw new DomainException('Only the squad leader can add players.');
            }
            if (! $mate->isStudent() || ! $leader->instructor_id || $mate->instructor_id !== $leader->instructor_id || $mate->id === $leader->id) {
                throw new DomainException('You can only add classmates from your own class.');
            }
            if (SquadMember::where('user_id', $mate->id)->exists()) {
                throw new DomainException('That player is already in a squad.');
            }

            $size = $squad ? $squad->memberships()->count() : 1;
            $max = $squad ? $squad->max_players : $this->max();
            if ($size >= $max) {
                throw new DomainException('The squad is full ('.$max.' players).');
            }

            if ($squad === null) {
                $squad = Squad::create(['leader_id' => $leader->id, 'max_players' => $this->max()]);
                SquadMember::create(['squad_id' => $squad->id, 'user_id' => $leader->id]);
            }
            SquadMember::create(['squad_id' => $squad->id, 'user_id' => $mate->id]);
        });
    }

    /** The leader removes another player. */
    public function remove(User $leader, User $member): void
    {
        DB::transaction(function () use ($leader, $member) {
            $squad = $this->squadOf($leader);
            if ($squad === null || $squad->leader_id !== $leader->id) {
                throw new DomainException('Only the squad leader can remove players.');
            }
            if ($member->id === $leader->id) {
                throw new DomainException('Use "Leave squad" to leave.');
            }
            $row = SquadMember::where('squad_id', $squad->id)->where('user_id', $member->id)->first();
            if ($row === null) {
                throw new DomainException('That player is not in your squad.');
            }
            $row->delete();
            $this->tidy($squad);
        });
    }

    public function leave(User $user): void
    {
        DB::transaction(function () use ($user) {
            $squad = $this->squadOf($user);
            if ($squad === null) {
                return;
            }
            SquadMember::where('squad_id', $squad->id)->where('user_id', $user->id)->delete();
            if ($squad->leader_id === $user->id) {
                $next = SquadMember::where('squad_id', $squad->id)->orderBy('id')->first();
                if ($next) {
                    $squad->update(['leader_id' => $next->user_id]);
                }
            }
            $this->tidy($squad);
        });
    }

    /** A squad with fewer than two players is just a solo player: remove it. */
    private function tidy(Squad $squad): void
    {
        if (SquadMember::where('squad_id', $squad->id)->count() < 2) {
            $squad->delete();      // members go with it (cascade)
        }
    }

    /** The squad for the web page: one entry per member with the public name and the look. */
    public function pageData(User $user): array
    {
        $squad = $this->squadOf($user);
        $members = $this->membersOf($user)->map(function (User $m) use ($user, $squad) {
            $view = $this->avatars->publicView($m);

            return [
                'id' => $m->id, 'callsign' => $view['callsign'], 'look' => $view['look'],
                'isYou' => $m->id === $user->id,
                'isLeader' => $squad === null ? $m->id === $user->id : $m->id === $squad->leader_id,
            ];
        })->values();

        $isLeader = $this->isLeader($user);
        $canAdd = $isLeader && $members->count() < ($squad ? $squad->max_players : $this->max());
        $candidates = $canAdd ? $this->candidates($user)->map(function (User $c) {
            $view = $this->avatars->publicView($c);

            return ['id' => $c->id, 'callsign' => $view['callsign'], 'look' => $view['look']];
        })->values() : collect();

        return [
            'members' => $members, 'size' => $members->count(), 'max' => $squad ? $squad->max_players : $this->max(),
            'isLeader' => $isLeader, 'canAdd' => $canAdd, 'candidates' => $candidates,
            'hasSquad' => $squad !== null, 'hasClass' => (bool) $user->instructor_id,
        ];
    }

    /** What Unity gets: the squad size (1 to 4) and every player's public name + look. */
    public function payload(User $user): array
    {
        $data = $this->pageData($user);

        return [
            'size' => $data['size'],
            'maxPlayers' => $data['max'],
            'minForMultiplayer' => (int) config('avatar.squad.min_for_multiplayer'),
            'isMultiplayer' => $data['size'] >= (int) config('avatar.squad.min_for_multiplayer'),
            'players' => $data['members']->map(fn ($m) => [
                'callsign' => $m['callsign'],
                'isYou' => $m['isYou'],
                'isLeader' => $m['isLeader'],
                'avatar' => $this->avatars->unityLook($m['look']),
            ])->values()->all(),
        ];
    }
}
