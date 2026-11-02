<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KaryawanSeeder extends Seeder
{
    /**
     * Tentukan sort_order hierarki KPU Bukittinggi sesuai aturan legacy:
     * Sekretaris = 1, Kasubag = 2, Penelaah = 3, Penyusun = 4, Pengolah = 5,
     * Ahli = 6, Operator = 7, Pengelola = 8, Default = 10
     */
    public static function determineSortOrder(string $namaJabatan): int
    {
        if (stripos($namaJabatan, 'Sekretaris') === 0) {
            return 1;
        } elseif (stripos($namaJabatan, 'Kasubag') === 0) {
            return 2;
        } elseif (stripos($namaJabatan, 'Penelaah') === 0) {
            return 3;
        } elseif (stripos($namaJabatan, 'Penyusun') === 0) {
            return 4;
        } elseif (stripos($namaJabatan, 'Pengolah') === 0) {
            return 5;
        } elseif (stripos($namaJabatan, 'Ahli') === 0) {
            return 6;
        } elseif (stripos($namaJabatan, 'Operator') === 0) {
            return 7;
        } elseif (stripos($namaJabatan, 'Pengelola') === 0) {
            return 8;
        }
        return 10;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $karyawans = [
            [
                'nip' => '197001012000011001',
                'nama' => 'Nama Pejabat',
                'jabatan' => 'Sekretaris KPU',
            ],
            [
                'nip' => '198505122010012002',
                'nama' => 'Sarah Mutia',
                'jabatan' => 'Kasubag Teknis Penyelenggaraan Pemilu, Partisipasi dan Hubungan Masyarakat',
            ],
            [
                'nip' => '198703102012012003',
                'nama' => 'Rina Andriani',
                'jabatan' => 'Kasubag Keuangan, Umum dan Logistik',
            ],
            [
                'nip' => '199003152015022003',
                'nama' => 'Liska Ismawati',
                'jabatan' => 'Penelaah Teknis Kebijakan',
            ],
            [
                'nip' => '199507202020121004',
                'nama' => 'Bagas Aditya',
                'jabatan' => 'Penyusun Bahan Informasi dan Publikasi',
            ],
            [
                'nip' => '199708172022031005',
                'nama' => 'Rahmat Hidayat',
                'jabatan' => 'Pengolah Data dan Informasi',
            ],
            [
                'nip' => '199811102023011006',
                'nama' => 'Fikri Pratama',
                'jabatan' => 'Ahli Pertama Pranata Komputer',
            ],
            [
                'nip' => '199912012024021007',
                'nama' => 'Dedi Kurniawan',
                'jabatan' => 'Operator Komputer Grafis',
            ],
            [
                'nip' => '200004052024032008',
                'nama' => 'Nurul Aini',
                'jabatan' => 'Pengelola Pengadaan Barang/Jasa',
            ],
            [
                'nip' => '200106152025011009',
                'nama' => 'Wahyu Saputra',
                'jabatan' => 'Staff Pelaksana',
            ],
        ];

        foreach ($karyawans as $data) {
            $jabatanRecord = DB::table('jabatans')->where('nama_jabatan', $data['jabatan'])->first();
            $jabatanId = $jabatanRecord ? $jabatanRecord->id : null;
            $sortOrder = self::determineSortOrder($data['jabatan']);

            DB::table('karyawans')->updateOrInsert(
                ['nip' => $data['nip']],
                [
                    'nama' => $data['nama'],
                    'jabatan_id' => $jabatanId,
                    'sort_order' => $sortOrder,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
