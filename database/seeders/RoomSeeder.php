<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Seeder;
use RuntimeException;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $path = base_path('content/demo-room.json');

        if (! file_exists($path)) {
            throw new RuntimeException('content/demo-room.json not found.');
        }

        $data = json_decode(file_get_contents($path), true);

        if (! is_array($data)) {
            throw new RuntimeException('content/demo-room.json is not valid JSON.');
        }

        Room::updateOrCreate(
            ['id' => $data['roomId']],
            [
                'name' => $data['name'],
                'time_limit_seconds' => $data['timeLimitSeconds'],
                // keep the whole content pack, plus the room-level concept tag
                // that the dashboards read
                'config' => $data + [
                    'conceptTag' => $data['puzzles'][0]['conceptTag'] ?? 'Boundary Testing',
                ],
            ]
        );
    }
}