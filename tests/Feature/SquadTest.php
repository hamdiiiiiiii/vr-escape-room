<?php

namespace Tests\Feature;

use App\Models\GameSession;
use App\Models\Room;
use App\Models\User;
use App\Services\AvatarService;
use App\Services\SquadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SquadTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teacher = User::factory()->create(['role' => 'instructor']);
    }

    private function kid(?User $teacher = null): User
    {
        return User::factory()->create(['role' => 'student', 'instructor_id' => ($teacher ?? $this->teacher)->id]);
    }

    public function test_a_squad_can_have_two_three_or_four_players_and_not_five(): void
    {
        $lead = $this->kid();
        $mates = [$this->kid(), $this->kid(), $this->kid(), $this->kid()];

        foreach (array_slice($mates, 0, 3) as $i => $m) {
            $this->actingAs($lead)->post('/student/squad/add', ['user_id' => $m->id])->assertSessionHasNoErrors();
            $this->assertSame($i + 2, app(SquadService::class)->pageData($lead)['size']);
        }
        $this->actingAs($lead)->post('/student/squad/add', ['user_id' => $mates[3]->id])->assertSessionHasErrors('squad');
        $this->assertSame(4, app(SquadService::class)->pageData($lead)['size']);
    }

    public function test_solo_player_is_size_one_and_not_multiplayer(): void
    {
        $lead = $this->kid();
        $payload = app(SquadService::class)->payload($lead);
        $this->assertSame(1, $payload['size']);
        $this->assertFalse($payload['isMultiplayer']);
        $this->assertCount(1, $payload['players']);
    }

    public function test_only_same_class_students_not_already_in_a_squad_can_be_added(): void
    {
        $lead = $this->kid();
        $other = $this->kid(User::factory()->create(['role' => 'instructor']));
        $this->actingAs($lead)->post('/student/squad/add', ['user_id' => $other->id])->assertSessionHasErrors('squad');
        $this->actingAs($lead)->post('/student/squad/add', ['user_id' => $lead->id])->assertSessionHasErrors('squad');

        $a = $this->kid();
        $b = $this->kid();
        $this->actingAs($lead)->post('/student/squad/add', ['user_id' => $a->id])->assertSessionHasNoErrors();
        $this->actingAs($b)->post('/student/squad/add', ['user_id' => $a->id])->assertSessionHasErrors('squad');
    }

    public function test_only_the_leader_adds_or_removes(): void
    {
        $lead = $this->kid();
        $a = $this->kid();
        $b = $this->kid();
        $this->actingAs($lead)->post('/student/squad/add', ['user_id' => $a->id])->assertSessionHasNoErrors();

        $this->actingAs($a)->post('/student/squad/add', ['user_id' => $b->id])->assertSessionHasErrors('squad');
        $this->actingAs($a)->delete('/student/squad/members/'.$lead->id)->assertSessionHasErrors('squad');
    }

    public function test_removing_down_to_one_player_deletes_the_squad(): void
    {
        $lead = $this->kid();
        $a = $this->kid();
        $this->actingAs($lead)->post('/student/squad/add', ['user_id' => $a->id]);
        $this->actingAs($lead)->delete('/student/squad/members/'.$a->id)->assertSessionHasNoErrors();

        $this->assertNull(app(SquadService::class)->squadOf($lead));
        $this->assertSame(1, app(SquadService::class)->pageData($lead)['size']);
    }

    public function test_leader_leaving_passes_leadership_on(): void
    {
        $lead = $this->kid();
        $a = $this->kid();
        $b = $this->kid();
        $this->actingAs($lead)->post('/student/squad/add', ['user_id' => $a->id]);
        $this->actingAs($lead)->post('/student/squad/add', ['user_id' => $b->id]);

        $this->actingAs($lead)->post('/student/squad/leave')->assertSessionHasNoErrors();
        $squad = app(SquadService::class)->squadOf($a);
        $this->assertNotNull($squad);
        $this->assertSame($a->id, $squad->leader_id);
        $this->assertNull(app(SquadService::class)->squadOf($lead));
    }

    public function test_page_shows_callsigns_only_for_squad_and_candidates(): void
    {
        $lead = $this->kid();
        $mate = $this->kid();
        $mate->forceFill(['name' => 'Real Child Name'])->save();
        app(AvatarService::class)->ensure($mate);
        $this->actingAs($lead)->get('/student/avatar')->assertOk()
            ->assertSee($mate->refresh()->callsign)->assertDontSee('Real Child Name');
    }

    public function test_squad_api_and_verify_code_carry_the_squad(): void
    {
        $lead = $this->kid();
        $a = $this->kid();
        $this->actingAs($lead)->post('/student/squad/add', ['user_id' => $a->id]);

        $room = Room::create(['name' => 'R', 'time_limit_seconds' => 600, 'config' => ['puzzles' => []]]);
        $session = GameSession::create(['user_id' => $lead->id, 'room_id' => $room->id, 'status' => 'in_progress', 'expires_at' => now()->addMinutes(10), 'started_at' => now()]);
        $token = $lead->createToken('session-'.$session->id)->plainTextToken;
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson("/api/sessions/{$session->id}/squad")->assertOk()
            ->assertJsonPath('size', 2)->assertJsonPath('isMultiplayer', true)->assertJsonCount(2, 'players')
            ->assertJsonMissingPath('players.0.email')->assertJsonMissingPath('players.0.name');

        GameSession::create(['user_id' => $lead->id, 'room_id' => $room->id, 'status' => 'waiting', 'code' => '246810', 'expires_at' => now()->addMinutes(10)]);
        $this->app['auth']->forgetGuards();
        $this->postJson('/api/sessions/verify-code', ['code' => '246810'])->assertOk()
            ->assertJsonPath('squad.size', 2)->assertJsonPath('squad.maxPlayers', 4);
    }

    public function test_old_looks_without_new_fields_still_work(): void
    {
        $look = app(AvatarService::class)->completeLook(['head' => 'head_01', 'outfit' => 'outfit_field']);
        foreach (['skin_tone', 'hair_color', 'held', 'headgear', 'gear', 'suit_color', 'team_color'] as $k) {
            $this->assertArrayHasKey($k, $look);
        }
    }
}
