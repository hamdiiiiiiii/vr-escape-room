<?php

namespace App\Services;

use App\Models\Room;

class RoomService
{
    /**
     * @return array<int, array{id: int, name: string, description: string, time_limit_minutes: int, concept_tag: string}>
     */
    public function getRooms(): array
    {
        return Room::orderBy('id')->get()->map(fn (Room $room) => $this->toArray($room))->all();
    }

    public function findRoom(int|string $id): ?array
    {
        $room = Room::find((int) $id);

        return $room ? $this->toArray($room) : null;
    }

    private function toArray(Room $room): array
    {
        $config = $room->config ?? [];

        return [
            'id' => $room->id,
            'name' => $room->name,
            'description' => $config['description'] ?? '',
            'time_limit_minutes' => (int) ceil($room->time_limit_seconds / 60),
            'concept_tag' => $config['conceptTag'] ?? ($config['puzzles'][0]['conceptTag'] ?? ''),
        ];
    }
}