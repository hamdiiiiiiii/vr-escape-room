<?php

namespace Tests\Feature;

use App\Models\GameSession;
use App\Models\Room;
use App\Models\User;
use App\Services\AvatarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvatarTest extends TestCase
{
    use RefreshDatabase;

    private function student(array $extra = []): User
    {
        return User::factory()->create(array_merge(['role' => 'student'], $extra));
    }

    private function look(array $over = []): array
    {
        return array_merge([
            'head' => 'head_03', 'skin_tone' => 'tone_3', 'hair_color' => 'black', 'outfit' => 'outfit_hazmat', 'headgear' => 'visor',
            'gear' => 'backpack', 'held' => 'flashlight', 'suit_color' => 'teal', 'team_color' => 'green',
            'name_first' => 'Raven', 'name_second' => 'Nova', 'name_number' => '77',
        ], $over);
    }

    public function test_student_sees_the_customize_page_and_gets_a_default_avatar(): void
    {
        $student = $this->student();

        $this->actingAs($student)->get('/student/avatar')
            ->assertOk()->assertSee('Players')->assertSee('Player Name')->assertSee('Join Game');

        $student->refresh();
        $this->assertNotNull($student->callsign);
        $this->assertContains($student->avatar['head'], app(AvatarService::class)->ids('heads'));
    }

    public function test_instructor_and_guest_cannot_use_the_page(): void
    {
        $this->get('/student/avatar')->assertRedirect('/login');
        $instructor = User::factory()->create(['role' => 'instructor']);
        $this->actingAs($instructor)->get('/student/avatar')->assertForbidden();
        $this->actingAs($instructor)->put('/student/avatar', $this->look())->assertForbidden();
    }

    public function test_saving_stores_the_look_and_builds_the_callsign(): void
    {
        $student = $this->student();

        $this->actingAs($student)->put('/student/avatar', $this->look())
            ->assertRedirect(route('student.avatar'))->assertSessionHasNoErrors();

        $student->refresh();
        $this->assertSame('RavenNova_77', $student->callsign);
        $this->assertSame('head_03', $student->avatar['head']);
        $this->assertSame('green', $student->avatar['team_color']);
        $this->assertSame(77, $student->avatar['name']['number']);
    }

    public function test_name_can_be_a_single_word_without_a_number(): void
    {
        $student = $this->student();
        $this->actingAs($student)->put('/student/avatar', $this->look(['name_second' => '', 'name_number' => '']))->assertSessionHasNoErrors();
        $this->assertSame('Raven', $student->refresh()->callsign);
    }

    public function test_only_catalogue_values_are_accepted_so_no_free_text_names(): void
    {
        $student = $this->student();

        foreach ([
            ['name_first' => 'John Smith'],
            ['name_first' => 'Raven', 'name_second' => 'badword'],
            ['name_number' => '100'],
            ['name_number' => 'abc'],
            ['head' => 'head_99'],
            ['held' => 'sword'],
            ['skin_tone' => '#fff'],
            ['outfit' => '<script>'],
            ['team_color' => '#ff0000'],
            ['gear' => 'sword'],
        ] as $bad) {
            $this->actingAs($student)->put('/student/avatar', $this->look($bad))->assertSessionHasErrors();
        }

        $this->assertNull($student->refresh()->callsign);
    }

    public function test_a_callsign_cannot_be_taken_by_two_students(): void
    {
        $a = $this->student();
        $b = $this->student();

        $this->actingAs($a)->put('/student/avatar', $this->look())->assertSessionHasNoErrors();
        $this->actingAs($b)->put('/student/avatar', $this->look())->assertSessionHasErrors('name_first');
        // the owner can save again with the same name
        $this->actingAs($a)->put('/student/avatar', $this->look(['head' => 'head_05']))->assertSessionHasNoErrors();
    }

    public function test_squad_shows_classmates_with_public_names_only(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $me = $this->student(['instructor_id' => $instructor->id]);
        $mate = $this->student(['instructor_id' => $instructor->id, 'name' => 'Real Child Name', 'email' => 'secret@example.com']);
        $stranger = $this->student();
        app(AvatarService::class)->ensure($mate);
        app(AvatarService::class)->ensure($stranger);

        $page = $this->actingAs($me)->get('/student/avatar')->assertOk();
        $page->assertSee($mate->refresh()->callsign);
        $page->assertDontSee('Real Child Name')->assertDontSee('secret@example.com')->assertDontSee($stranger->refresh()->callsign);
    }

    public function test_options_endpoint_returns_the_catalogue_without_login(): void
    {
        $this->getJson('/api/avatar/options')->assertOk()
            ->assertJsonStructure(['version', 'heads' => [['id', 'gender', 'hairStyle']], 'skinTones', 'hairColors', 'outfits', 'headgear', 'gear', 'held', 'suitColors', 'teamColors', 'nameWords', 'defaults', 'squad' => ['maxPlayers', 'minForMultiplayer']])
            ->assertJsonMissingPath('heads.0.label')
            ->assertJsonCount(count(config('avatar.heads')), 'heads');
    }

    private function inProgress(User $user): array
    {
        $room = Room::create(['name' => 'R', 'time_limit_seconds' => 600, 'config' => ['puzzles' => []]]);
        $session = GameSession::create(['user_id' => $user->id, 'room_id' => $room->id, 'status' => 'in_progress', 'expires_at' => now()->addMinutes(10), 'started_at' => now()]);

        return [$session, $user->createToken('session-'.$session->id)->plainTextToken];
    }

    public function test_verify_code_returns_the_player_block(): void
    {
        $user = $this->student();
        app(AvatarService::class)->save($user, app(AvatarService::class)->normalise($this->look()));
        $room = Room::create(['name' => 'R', 'time_limit_seconds' => 600, 'config' => ['puzzles' => []]]);
        GameSession::create(['user_id' => $user->id, 'room_id' => $room->id, 'status' => 'waiting', 'code' => '123456', 'expires_at' => now()->addMinutes(10)]);

        $this->postJson('/api/sessions/verify-code', ['code' => '123456'])->assertOk()
            ->assertJsonPath('player.callsign', 'RavenNova_77')
            ->assertJsonPath('player.avatar.head', 'head_03')
            ->assertJsonPath('player.avatar.suitColor', 'teal')
            ->assertJsonPath('player.avatar.teamColor', 'green')
            ->assertJsonMissingPath('player.name')->assertJsonMissingPath('player.email');
    }

    public function test_verify_code_gives_a_default_avatar_to_a_student_who_never_customised(): void
    {
        $user = $this->student();
        $room = Room::create(['name' => 'R', 'time_limit_seconds' => 600, 'config' => ['puzzles' => []]]);
        GameSession::create(['user_id' => $user->id, 'room_id' => $room->id, 'status' => 'waiting', 'code' => '654321', 'expires_at' => now()->addMinutes(10)]);

        $res = $this->postJson('/api/sessions/verify-code', ['code' => '654321'])->assertOk();
        $this->assertNotEmpty($res->json('player.callsign'));
        $this->assertSame($user->refresh()->callsign, $res->json('player.callsign'));
    }

    public function test_session_avatar_endpoint_needs_the_matching_token(): void
    {
        $user = $this->student();
        [$session, $token] = $this->inProgress($user);
        $other = $this->student();
        [, $otherToken] = $this->inProgress($other);

        $this->withToken($token)->getJson("/api/sessions/{$session->id}/avatar")->assertOk()->assertJsonStructure(['callsign', 'avatar' => ['head', 'skinTone', 'hairColor', 'outfit', 'headgear', 'gear', 'held', 'suitColor', 'teamColor']]);
        $this->app['auth']->forgetGuards();   // tests reuse the app between requests: forget the first token's user
        $this->withToken($otherToken)->getJson("/api/sessions/{$session->id}/avatar")->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->withHeaders(['Authorization' => ''])->getJson("/api/sessions/{$session->id}/avatar")->assertUnauthorized();
    }

    public function test_every_default_avatar_is_valid_and_unique_for_many_students(): void
    {
        $service = app(AvatarService::class);
        $names = [];
        foreach (range(1, 40) as $i) {
            $u = $this->student();
            $look = $service->ensure($u);
            $this->assertContains($look['head'], $service->ids('heads'));
            $this->assertContains($look['name']['first'], $service->ids('name_words'));
            $names[] = $u->refresh()->callsign;
        }
        $this->assertSame(count($names), count(array_unique($names)));
    }
}
