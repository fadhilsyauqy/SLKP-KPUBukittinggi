<?php

namespace Database\Factories;

use App\Models\Jabatan;
use App\Models\Karyawan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Karyawan>
 */
class KaryawanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $jabatan = Jabatan::factory()->create();

        return [
            'nip' => fake()->unique()->numerify('19##########00##'),
            'nama' => fake()->name(),
            'jabatan_id' => $jabatan->id,
            'sort_order' => Karyawan::determineSortOrder($jabatan->nama_jabatan),
        ];
    }
}
