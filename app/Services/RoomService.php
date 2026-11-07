<?php

// STUB: replace with Member 5's API/database later.

namespace App\Services;

class RoomService
{
    /**
     * Get all available escape rooms.
     *
     * @return array<int, array{id: int, name: string, description: string, time_limit_minutes: int, concept_tag: string}>
     */
    public function getRooms(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'The Boundary Maze',
                'description' => 'Locate boundary conditions and off-by-one errors to unlock pressurized airlock doors.',
                'time_limit_minutes' => 15,
                'concept_tag' => 'Boundary Testing',
            ],
            [
                'id' => 2,
                'name' => 'Equivalence Chamber',
                'description' => 'Classify valid and invalid inputs into partitions to deactivate high-voltage test gates.',
                'time_limit_minutes' => 20,
                'concept_tag' => 'Equivalence Partitioning',
            ],
            [
                'id' => 3,
                'name' => 'The Assertion Vault',
                'description' => 'Formulate assertions and verify system states under stress to reveal the exit keycode.',
                'time_limit_minutes' => 25,
                'concept_tag' => 'Assertions & Test Suites',
            ],
        ];
    }

    /**
     * Find a specific room by its identifier.
     *
     * @param int|string $id
     * @return array{id: int, name: string, description: string, time_limit_minutes: int, concept_tag: string}|null
     */
    public function findRoom(int|string $id): ?array
    {
        foreach ($this->getRooms() as $room) {
            if ((int) $room['id'] === (int) $id) {
                return $room;
            }
        }

        return null;
    }
}
