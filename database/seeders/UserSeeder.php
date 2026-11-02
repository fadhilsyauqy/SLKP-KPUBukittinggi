<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Akun Pengujian Utama Terhubung ke Pejabat & Pegawai KPU Bukittinggi
        $users = [
            [
                'name' => 'Administrator KPU',
                'username' => 'admin',
                'nip' => null,
                'email' => 'admin@kpubukittinggi.go.id',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ],
            [
                'name' => 'Pegawai Contoh 01',
                'username' => 'supervisor',
                'nip' => '19700101 200001 1 001',
                'email' => 'supervisor@kpubukittinggi.go.id',
                'password' => Hash::make('supervisor123'),
                'role' => 'supervisor',
            ],
            [
                'name' => 'Pegawai Contoh 06',
                'username' => 'karyawan',
                'nip' => '197001062000011006',
                'email' => 'karyawan@kpubukittinggi.go.id',
                'password' => Hash::make('karyawan123'),
                'role' => 'karyawan',
            ],
        ];

        $assignedNips = [];
        foreach ($users as $user) {
            if ($user['nip']) {
                $assignedNips[] = $user['nip'];
            }
            DB::table('users')->updateOrInsert(
                ['username' => $user['username']],
                [
                    'name' => $user['name'],
                    'nip' => $user['nip'],
                    'email' => $user['email'],
                    'password' => $user['password'],
                    'role' => $user['role'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 2. Akun Pengguna Khusus Seluruh Anggota Resmi KPU Bukittinggi
        // Login: NIP (tanpa spasi), Password bawaan: password123
        $defaultPassword = Hash::make('password123');
        $allKaryawans = DB::table('karyawans')
            ->leftJoin('jabatans', 'karyawans.jabatan_id', '=', 'jabatans.id')
            ->select('karyawans.*', 'jabatans.nama_jabatan')
            ->get();

        foreach ($allKaryawans as $karyawan) {
            // Lewati NIP yang sudah ditugaskan ke akun pengujian supervisor dan karyawan
            if (in_array($karyawan->nip, $assignedNips)) {
                continue;
            }

            $cleanNip = preg_replace('/\s+/', '', $karyawan->nip);
            // Sekretaris & Kasubag (sort_order 1 & 2) diberi role supervisor untuk monitoring, staf lainnya karyawan
            $role = in_array((int) $karyawan->sort_order, [1, 2]) ? 'supervisor' : 'karyawan';
            $email = strtolower($cleanNip) . '@kpubukittinggi.go.id';

            DB::table('users')->updateOrInsert(
                ['username' => $cleanNip],
                [
                    'name' => $karyawan->nama,
                    'nip' => $karyawan->nip,
                    'email' => $email,
                    'password' => $defaultPassword,
                    'role' => $role,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
