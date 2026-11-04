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
            ['nama' => 'Sekretaris', 'sub_bagian' => 'Sekretariat'],
            ['nama' => 'Sekretaris KPU', 'sub_bagian' => 'Sekretariat'],
            ['nama' => 'Kasubag Teknis dan Hukum', 'sub_bagian' => 'Teknis'],
            ['nama' => 'Kasubag Parhubmas dan SDM', 'sub_bagian' => 'Hukum & SDM'],
            ['nama' => 'Kasubag Perencanaan Data dan Informasi', 'sub_bagian' => 'Rendatin'],
            ['nama' => 'Kasubag Keuangan, Umum dan Logistik', 'sub_bagian' => 'KUL'],
            ['nama' => 'Ahli Pertama - Penata Kelola Pemilihan Umum', 'sub_bagian' => null],
            ['nama' => 'Ahli Pertama - Pranata Komputer', 'sub_bagian' => 'Rendatin'],
            ['nama' => 'Pengelola Layanan Operasional', 'sub_bagian' => null],
            ['nama' => 'Pengelola Umum Operasional', 'sub_bagian' => 'KUL'],
            ['nama' => 'Operator Layanan Operasional', 'sub_bagian' => null],
            ['nama' => 'Penelaah Teknis Kebijakan', 'sub_bagian' => 'Teknis'],
            ['nama' => 'Penyusun Materi Hukum dan Perundang-undangan', 'sub_bagian' => 'Hukum & SDM'],
            ['nama' => 'Pengadministrasi Perkantoran', 'sub_bagian' => 'KUL'],

            // Nomenklatur Jabatan Sebelumnya & Pendukung
            ['nama' => 'Kasubag Teknis Penyelenggaraan Pemilu, Partisipasi dan Hubungan Masyarakat', 'sub_bagian' => 'Teknis'],
            ['nama' => 'Kasubag Perencanaan, Data dan Informasi', 'sub_bagian' => 'Rendatin'],
            ['nama' => 'Kasubag Hukum dan Sumber Daya Manusia', 'sub_bagian' => 'Hukum & SDM'],
            ['nama' => 'Penyusun Bahan Informasi dan Publikasi', 'sub_bagian' => 'Teknis'],
            ['nama' => 'Pengolah Data dan Informasi', 'sub_bagian' => 'Rendatin'],
            ['nama' => 'Ahli Pertama Pranata Komputer', 'sub_bagian' => 'Rendatin'],
            ['nama' => 'Operator Komputer Grafis', 'sub_bagian' => 'Rendatin'],
            ['nama' => 'Pengelola Pengadaan Barang/Jasa', 'sub_bagian' => 'KUL'],
            ['nama' => 'Staff Pelaksana', 'sub_bagian' => 'Sekretariat'],
            ['nama' => 'Staff', 'sub_bagian' => null],
        ];

        foreach ($jabatans as $item) {
            DB::table('jabatans')->updateOrInsert(
                ['nama_jabatan' => $item['nama']],
                [
                    'sub_bagian' => $item['sub_bagian'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
