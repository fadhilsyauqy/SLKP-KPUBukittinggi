<?php

namespace Database\Factories;

use App\Models\HariLibur;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HariLibur>
 */
class HariLiburFactory extends Factory
{
    protected $model = HariLibur::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tanggal' => fake()->unique()->date(),
            'keterangan' => fake()->sentence(3),
            'is_cuti_bersama' => fake()->boolean(20),
        ];
    }

    /**
     * State untuk cuti bersama.
     */
    public function cutiBersama(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_cuti_bersama' => true,
        ]);
    }

    /**
     * State untuk hari libur nasional resmi.
     */
    public function liburNasional(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_cuti_bersama' => false,
        ]);
    }
}
