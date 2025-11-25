<?php

namespace Database\Factories;

use App\Models\Meeting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MeetingInfo>
 */
class MeetingInfoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = $this->faker->randomElement(['pending', 'processing', 'done', 'failed']);

        // สร้าง summaries เฉพาะเมื่อ status เป็น 'done'
        $summaries = null;
        if ($status === 'done') {
            $summaries = [
                'overview' => $this->faker->paragraph(3),
                'key_points' => [
                    $this->faker->sentence(),
                    $this->faker->sentence(),
                    $this->faker->sentence(),
                ],
                'action_items' => [
                    [
                        'task' => $this->faker->sentence(),
                        'assignee' => $this->faker->name(),
                        'due_date' => $this->faker->date(),
                    ],
                    [
                        'task' => $this->faker->sentence(),
                        'assignee' => $this->faker->name(),
                        'due_date' => $this->faker->date(),
                    ],
                ],
                'participants' => [
                    'SPEAKER_00' => $this->faker->name(),
                    'SPEAKER_01' => $this->faker->name(),
                    'SPEAKER_02' => $this->faker->name(),
                ],
                'decisions' => [
                    $this->faker->sentence(),
                    $this->faker->sentence(),
                ],
            ];
        }

        return [
            'description' => $this->faker->sentence(4),
            'meeting_id' => Meeting::inRandomOrder()->first(),
            'status' => $status,
            'summaries' => $summaries,
            'media_paths' => $status === 'done'
                ? 'https://example.com/media/' . $this->faker->uuid . '.mp4'
                : null,
            'media_object_key' => $status === 'done'
                ? 'meetings/' . $this->faker->uuid . '.mp4'
                : null,
        ];
    }
}
