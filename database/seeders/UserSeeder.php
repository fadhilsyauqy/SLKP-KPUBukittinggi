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
                'name' => 'Sarah Mutia',
                'username' => 'supervisor',
                'nip' => '198505122010012002',
                'email' => 'supervisor@kpubukittinggi.go.id',
                'password' => Hash::make('supervisor123'),
                'role' => 'supervisor',
            ],
            [
                'name' => 'Liska Ismawati',
                'username' => 'karyawan',
                'nip' => '199003152015022003',
                'email' => 'karyawan@kpubukittinggi.go.id',
                'password' => Hash::make('karyawan123'),
                'role' => 'karyawan',
            ],
        ];

        foreach ($users as $user) {
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
    }
}
