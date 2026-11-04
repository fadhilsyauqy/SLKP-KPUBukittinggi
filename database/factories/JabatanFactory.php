<?php

namespace Database\Factories;

use App\Models\Jabatan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Jabatan>
 */
class JabatanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama_jabatan' => fake()->unique()->jobTitle(),
            'sub_bagian' => null,
        ];
    }

    /**
     * Indicate the specific sub_bagian.
     */
    public function subBagian(string $subBagian): static
    {
        return $this->state(fn (array $attributes) => [
            'sub_bagian' => $subBagian,
        ]);
    }
}
