<?php

namespace Database\Factories;

use App\Models\Laporan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Laporan>
 */
class LaporanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nip' => fake()->numerify('19##########00##'),
            'nama' => fake()->name(),
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tipe' => 'hadir',
            'tanggal' => fake()->date(),
            'jam_masuk' => '07:30',
            'jam_keluar' => '16:00',
            'uraian' => fake()->sentence(8),
            'output' => fake()->sentence(4),
            'status_sinkron_google' => false,
            'catatan_sinkron' => null,
        ];
    }

    /**
     * State untuk laporan tidak hadir.
     */
    public function tidakHadir(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipe' => 'tidak_hadir',
            'jam_masuk' => null,
            'jam_keluar' => null,
            'keterangan' => fake()->randomElement(['Cuti', 'Izin', 'Sakit', 'Perjadin']),
            'alasan' => fake()->sentence(6),
            'tanggal_selesai' => fake()->date(),
            'lama_hari' => 1,
            'bukti' => null,
        ]);
    }
}
