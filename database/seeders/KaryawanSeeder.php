<?php

namespace Database\Seeders;

use App\Models\Karyawan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KaryawanSeeder extends Seeder
{
    /**
     * Tentukan sort_order hierarki KPU Bukittinggi sesuai aturan:
     * Sekretaris = 1, Kasubag = 2, Penelaah = 3, Penyusun = 4, Pengolah = 5,
     * Ahli = 6, Operator = 7, Pengelola = 8, Pengadministrasi = 9, Default = 10
     */
    public static function determineSortOrder(string $namaJabatan): int
    {
        return Karyawan::determineSortOrder($namaJabatan);
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 30 Data Anggota & Jabatan Resmi Sekretariat KPU Kota Bukittinggi
        $karyawans = [
            // Gambar 1
            [
                'nip' => '19700101 200001 1 001',
                'nama' => 'Pegawai Contoh 01',
                'jabatan' => 'Sekretaris',
            ],
            [
                'nip' => '19700102 200001 1 002',
                'nama' => 'Pegawai Contoh 02',
                'jabatan' => 'Kasubag Teknis dan Hukum',
            ],
            [
                'nip' => '19700103 200001 1 003',
                'nama' => 'Pegawai Contoh 03',
                'jabatan' => 'Kasubag Parhubmas dan SDM',
            ],
            [
                'nip' => '19700104 200001 1 004',
                'nama' => 'Pegawai Contoh 04',
                'jabatan' => 'Kasubag Perencanaan Data dan Informasi',
            ],
            [
                'nip' => '19700105 200001 1 005',
                'nama' => 'Pegawai Contoh 05',
                'jabatan' => 'Kasubag Keuangan, Umum dan Logistik',
            ],

            // Gambar 2
            [
                'nip' => '197001062000011006',
                'nama' => 'Pegawai Contoh 06',
                'jabatan' => 'Ahli Pertama - Penata Kelola Pemilihan Umum',
            ],
            [
                'nip' => '197001072000011007',
                'nama' => 'Pegawai Contoh 07',
                'jabatan' => 'Ahli Pertama - Penata Kelola Pemilihan Umum',
            ],
            [
                'nip' => '197001082000011008',
                'nama' => 'Pegawai Contoh 08',
                'jabatan' => 'Ahli Pertama - Penata Kelola Pemilihan Umum',
            ],
            [
                'nip' => '197001092000011009',
                'nama' => 'Pegawai Contoh 09',
                'jabatan' => 'Ahli Pertama - Penata Kelola Pemilihan Umum',
            ],
            [
                'nip' => '19700110 200001 1 010',
                'nama' => 'Pegawai Contoh 10',
                'jabatan' => 'Ahli Pertama - Penata Kelola Pemilihan Umum',
            ],

            // Gambar 3
            [
                'nip' => '19700111 200001 1 011',
                'nama' => 'Pegawai Contoh 11',
                'jabatan' => 'Ahli Pertama - Penata Kelola Pemilihan Umum',
            ],
            [
                'nip' => '19700112 200001 1 012',
                'nama' => 'Pegawai Contoh 12',
                'jabatan' => 'Ahli Pertama - Penata Kelola Pemilihan Umum',
            ],
            [
                'nip' => '19700113 200001 1 013',
                'nama' => 'Pegawai Contoh 13',
                'jabatan' => 'Ahli Pertama - Penata Kelola Pemilihan Umum',
            ],
            [
                'nip' => '19700114 200001 1 014',
                'nama' => 'Pegawai Contoh 14',
                'jabatan' => 'Ahli Pertama - Penata Kelola Pemilihan Umum',
            ],
            [
                'nip' => '19700115 200001 1 015',
                'nama' => 'Pegawai Contoh 15',
                'jabatan' => 'Ahli Pertama - Penata Kelola Pemilihan Umum',
            ],

            // Gambar 4
            [
                'nip' => '19700116 200001 1 016',
                'nama' => 'Pegawai Contoh 16',
                'jabatan' => 'Ahli Pertama - Pranata Komputer',
            ],
            [
                'nip' => '197001172000011017',
                'nama' => 'Pegawai Contoh 17',
                'jabatan' => 'Pengelola Layanan Operasional',
            ],
            [
                'nip' => '197001182000011018',
                'nama' => 'Pegawai Contoh 18',
                'jabatan' => 'Pengelola Umum Operasional',
            ],
            [
                'nip' => '197001192000011019',
                'nama' => 'Pegawai Contoh 19',
                'jabatan' => 'Operator Layanan Operasional',
            ],
            [
                'nip' => '197001202000011020',
                'nama' => 'Pegawai Contoh 20',
                'jabatan' => 'Penelaah Teknis Kebijakan',
            ],

            // Gambar 5
            [
                'nip' => '197001212000011021',
                'nama' => 'Pegawai Contoh 21',
                'jabatan' => 'Operator Layanan Operasional',
            ],
            [
                'nip' => '197001222000011022',
                'nama' => 'Pegawai Contoh 22',
                'jabatan' => 'Penyusun Materi Hukum dan Perundang-undangan',
            ],
            [
                'nip' => '197001232000011023',
                'nama' => 'Pegawai Contoh 23',
                'jabatan' => 'Pengadministrasi Perkantoran',
            ],
            [
                'nip' => '197001242000011024',
                'nama' => 'Pegawai Contoh 24',
                'jabatan' => 'Pengadministrasi Perkantoran',
            ],
            [
                'nip' => '197001252000011025',
                'nama' => 'Pegawai Contoh 25',
                'jabatan' => 'Operator Layanan Operasional',
            ],

            // Gambar 6
            [
                'nip' => '197001262000011026',
                'nama' => 'Pegawai Contoh 26',
                'jabatan' => 'Penyusun Materi Hukum dan Perundang-undangan',
            ],
            [
                'nip' => '19700127 200001 1 027',
                'nama' => 'Pegawai Contoh 27',
                'jabatan' => 'Penelaah Teknis Kebijakan',
            ],
            [
                'nip' => '19700128 200001 1 028',
                'nama' => 'Pegawai Contoh 28',
                'jabatan' => 'Penelaah Teknis Kebijakan',
            ],
            [
                'nip' => '19700129 200001 1 029',
                'nama' => 'Pegawai Contoh 29',
                'jabatan' => 'Penelaah Teknis Kebijakan',
            ],
            [
                'nip' => '19700130 200001 1 030',
                'nama' => 'Pegawai Contoh 30',
                'jabatan' => 'Penelaah Teknis Kebijakan',
            ],
        ];

        foreach ($karyawans as $data) {
            $jabatanRecord = DB::table('jabatans')->where('nama_jabatan', $data['jabatan'])->first();
            $jabatanId = $jabatanRecord ? $jabatanRecord->id : null;
            $subBagian = $jabatanRecord ? ($jabatanRecord->sub_bagian ?? null) : null;
            $sortOrder = self::determineSortOrder($data['jabatan']);

            DB::table('karyawans')->updateOrInsert(
                ['nip' => $data['nip']],
                [
                    'nama' => $data['nama'],
                    'jabatan_id' => $jabatanId,
                    'sub_bagian' => $subBagian,
                    'sort_order' => $sortOrder,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
