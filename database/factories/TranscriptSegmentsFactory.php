<?php

namespace Database\Factories;

use App\Models\MeetingInfo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TranscriptSegments>
 */
class TranscriptSegmentsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = $this->faker->randomFloat(3, 0, 300);
        $end = $start + $this->faker->randomFloat(3, 3, 30);
        $text = $this->faker->sentence(12);
        $speaker = $this->faker->randomElement(['SPEAKER_00', 'SPEAKER_01', 'SPEAKER_02']);
        $tag = $this->faker->randomElement(['use', 'remove']);
        $hasOverlap = $this->faker->boolean(20); // 20% มี overlap

        return [
            'meeting_info_id' => MeetingInfo::factory(),
            'idx' => 0, // จะถูกกำหนดใหม่ใน seeder
            'start' => $start,
            'end' => $end,
            'text' => $text,
            'llm_corrected_text' => $this->faker->boolean(70)
                ? $this->faker->sentence(12)
                : null,
            'speaker' => $speaker,
            'filename' => sprintf('segment_%03d_%s.wav', $this->faker->numberBetween(0, 999), $speaker),
            'avg_probability' => $this->faker->randomFloat(4, 0.5, 1.0),
            'confidence' => $this->faker->randomFloat(8, 0.3, 0.9),
            'tag' => $tag,
            'is_remove' => $tag === 'remove',
            'remove_reason' => $tag === 'remove'
                ? $this->faker->randomElement([
                    'segment_duration<3.0s',
                    'low_confidence',
                    'noise_detected',
                    'duplicate_speech'
                ])
                : null,
            'has_overlap' => $hasOverlap,
            'overlap_ratio' => $hasOverlap ? $this->faker->randomFloat(4, 0.1, 0.5) : null,
            'overlap_intervals' => $hasOverlap ? [
                [$start + 1, $start + 2],
                [$end - 1, $end],
            ] : null,
        ];
    }

    /**
     * State สำหรับ segment ที่มีคุณภาพดี (use)
     */
    public function highQuality(): static
    {
        return $this->state(fn(array $attributes) => [
            'tag' => 'use',
            'is_remove' => false,
            'remove_reason' => null,
            'avg_probability' => $this->faker->randomFloat(4, 0.85, 1.0),
            'confidence' => $this->faker->randomFloat(8, 0.7, 0.95),
            'has_overlap' => false,
            'overlap_ratio' => null,
            'overlap_intervals' => null,
        ]);
    }

    /**
     * State สำหรับ segment ที่ถูก remove
     */
    public function removed(): static
    {
        return $this->state(fn(array $attributes) => [
            'tag' => 'remove',
            'is_remove' => true,
            'remove_reason' => 'segment_duration<3.0s',
            'avg_probability' => $this->faker->randomFloat(4, 0.1, 0.5),
            'confidence' => $this->faker->randomFloat(8, 0.1, 0.4),
        ]);
    }
}
