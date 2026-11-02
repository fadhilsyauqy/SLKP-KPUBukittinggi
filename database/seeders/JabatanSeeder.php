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
            'Sekretaris KPU',
            'Kasubag Keuangan, Umum dan Logistik',
            'Kasubag Teknis Penyelenggaraan Pemilu, Partisipasi dan Hubungan Masyarakat',
            'Kasubag Perencanaan, Data dan Informasi',
            'Kasubag Hukum dan Sumber Daya Manusia',
            'Penelaah Teknis Kebijakan',
            'Penyusun Bahan Informasi dan Publikasi',
            'Pengolah Data dan Informasi',
            'Ahli Pertama Pranata Komputer',
            'Operator Komputer Grafis',
            'Pengelola Pengadaan Barang/Jasa',
            'Staff Pelaksana',
            // Data jabatan dari arsip lama
            'CEO',
            'Office Boy',
            'Accounting',
            'Operator',
            'Direktur',
            'Kepala Toko',
            'Marketing',
        ];

        foreach ($jabatans as $jabatan) {
            DB::table('jabatans')->updateOrInsert(
                ['nama_jabatan' => $jabatan],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
