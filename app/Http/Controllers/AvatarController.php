<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAvatarRequest;
use App\Models\User;
use App\Services\AvatarService;
use App\Services\SquadService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** The student's "Customize" page: pick a look and a public player name, and manage the squad. Students only. */
class AvatarController extends Controller
{
    public function show(Request $request, AvatarService $avatars, SquadService $squads): View
    {
        $student = $request->user();
        $look = $avatars->ensure($student);

        return view('student.customize', [
            'student' => $student,
            'look' => $look,
            'catalog' => $avatars->catalog(),
            'squad' => $squads->pageData($student),
        ]);
    }

    public function update(UpdateAvatarRequest $request, AvatarService $avatars): RedirectResponse
    {
        $avatars->save($request->user(), $avatars->normalise($request->validated()));

        if ($request->boolean('join')) {
            return redirect()->route('student.dashboard')->with('status', 'avatar-saved');
        }

        return redirect()->route('student.avatar')->with('status', 'avatar-saved');
    }

    public function addPlayer(Request $request, SquadService $squads): RedirectResponse
    {
        $data = $request->validate(['user_id' => ['required', 'integer']]);
        $mate = User::find($data['user_id']);

        return $this->squadAction(fn () => $squads->add($request->user(), $mate ?? throw new DomainException('Player not found.')), 'squad-added');
    }

    public function removePlayer(Request $request, int $id, SquadService $squads): RedirectResponse
    {
        $member = User::find($id);

        return $this->squadAction(fn () => $squads->remove($request->user(), $member ?? throw new DomainException('Player not found.')), 'squad-removed');
    }

    public function leaveSquad(Request $request, SquadService $squads): RedirectResponse
    {
        return $this->squadAction(fn () => $squads->leave($request->user()), 'squad-left');
    }

    private function squadAction(callable $action, string $status): RedirectResponse
    {
        try {
            $action();
        } catch (DomainException $e) {
            return redirect()->route('student.avatar')->withErrors(['squad' => $e->getMessage()]);
        }

        return redirect()->route('student.avatar')->with('status', $status);
    }
}
