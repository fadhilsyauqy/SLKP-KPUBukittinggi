<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JabatanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jabatans = [
            // Jabatan Resmi Sekretariat KPU Kota Bukittinggi (Struktur Aktual)
            'Sekretaris',
            'Sekretaris KPU',
            'Kasubag Teknis dan Hukum',
            'Kasubag Parhubmas dan SDM',
            'Kasubag Perencanaan Data dan Informasi',
            'Kasubag Keuangan, Umum dan Logistik',
            'Ahli Pertama - Penata Kelola Pemilihan Umum',
            'Ahli Pertama - Pranata Komputer',
            'Pengelola Layanan Operasional',
            'Pengelola Umum Operasional',
            'Operator Layanan Operasional',
            'Penelaah Teknis Kebijakan',
            'Penyusun Materi Hukum dan Perundang-undangan',
            'Pengadministrasi Perkantoran',

            // Nomenklatur Jabatan Sebelumnya & Pendukung
            'Kasubag Teknis Penyelenggaraan Pemilu, Partisipasi dan Hubungan Masyarakat',
            'Kasubag Perencanaan, Data dan Informasi',
            'Kasubag Hukum dan Sumber Daya Manusia',
            'Penyusun Bahan Informasi dan Publikasi',
            'Pengolah Data dan Informasi',
            'Ahli Pertama Pranata Komputer',
            'Operator Komputer Grafis',
            'Pengelola Pengadaan Barang/Jasa',
            'Staff Pelaksana',
            'Staff',
        ];

        foreach ($jabatans as $jabatan) {
            DB::table('jabatans')->updateOrInsert(
                ['nama_jabatan' => $jabatan],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
