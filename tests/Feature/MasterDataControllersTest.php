<?php

namespace Tests\Feature;

use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataControllersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_karyawan_crud_and_automatic_sort_order(): void
    {
        $admin = User::where('username', 'admin')->first();
        $supervisor = User::where('username', 'supervisor')->first();

        // 1. Non-admin dilarang akses
        $this->actingAs($supervisor)->get('/admin/karyawan')->assertStatus(403);

        // 2. Admin dapat melihat daftar
        $response = $this->actingAs($admin)->get('/admin/karyawan');
        $response->assertStatus(200);
        $response->assertSee('Master Data Karyawan');

        // 3. Admin tambah karyawan dengan jabatan Kasubag (sort_order harus 2)
        $jabatanKasubag = Jabatan::where('nama_jabatan', 'Kasubag Keuangan, Umum dan Logistik')->first();

        $postResponse = $this->actingAs($admin)->post('/admin/karyawan', [
            'nip' => '199201012020011005',
            'nama' => 'Budi Santoso',
            'jabatan_id' => $jabatanKasubag->id,
        ]);
        $postResponse->assertRedirect('/admin/karyawan');

        $karyawan = Karyawan::where('nip', '199201012020011005')->first();
        $this->assertNotNull($karyawan);
        $this->assertEquals(2, $karyawan->sort_order);

        // 4. Admin update jabatan ke Sekretaris (sort_order harus berubah jadi 1)
        $jabatanSekretaris = Jabatan::where('nama_jabatan', 'Sekretaris KPU')->first();
        $this->actingAs($admin)->put('/admin/karyawan/'.$karyawan->id, [
            'nip' => '199201012020011005',
            'nama' => 'Budi Santoso M.Si',
            'jabatan_id' => $jabatanSekretaris->id,
        ]);

        $karyawan->refresh();
        $this->assertEquals(1, $karyawan->sort_order);
        $this->assertEquals('Budi Santoso M.Si', $karyawan->nama);

        // 5. Admin hapus karyawan
        $this->actingAs($admin)->delete('/admin/karyawan/'.$karyawan->id)->assertRedirect('/admin/karyawan');
        $this->assertDatabaseMissing('karyawans', ['nip' => '199201012020011005']);
    }

    public function test_jabatan_crud(): void
    {
        $admin = User::where('username', 'admin')->first();

        // 1. Admin tambah jabatan
        $this->actingAs($admin)->post('/admin/jabatan', [
            'nama_jabatan' => 'Tenaga Pendukung Teknis',
        ])->assertRedirect('/admin/jabatan');

        $jabatan = Jabatan::where('nama_jabatan', 'Tenaga Pendukung Teknis')->first();
        $this->assertNotNull($jabatan);

        // 2. Admin edit jabatan
        $this->actingAs($admin)->put('/admin/jabatan/'.$jabatan->id, [
            'nama_jabatan' => 'Tenaga Pendukung Teknis Pemilu',
        ])->assertRedirect('/admin/jabatan');

        $jabatan->refresh();
        $this->assertEquals('Tenaga Pendukung Teknis Pemilu', $jabatan->nama_jabatan);

        // 3. Admin hapus jabatan
        $this->actingAs($admin)->delete('/admin/jabatan/'.$jabatan->id)->assertRedirect('/admin/jabatan');
        $this->assertDatabaseMissing('jabatans', ['id' => $jabatan->id]);
    }

    public function test_user_crud_and_cannot_delete_self(): void
    {
        $admin = User::where('username', 'admin')->first();

        // 1. Admin buat user baru
        $this->actingAs($admin)->post('/admin/user', [
            'name' => 'Operator Baru',
            'username' => 'operator1',
            'password' => 'operator123',
            'role' => 'karyawan',
        ])->assertRedirect('/admin/user');

        $user = User::where('username', 'operator1')->first();
        $this->assertNotNull($user);
        $this->assertEquals('karyawan', $user->role);

        // 2. Admin dilarang menghapus akunnya sendiri
        $this->actingAs($admin)->delete('/admin/user/'.$admin->id)->assertStatus(403);

        // 3. Admin boleh menghapus akun user lain
        $this->actingAs($admin)->delete('/admin/user/'.$user->id)->assertRedirect('/admin/user');
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_pagination_renders_modern_controls_without_redundant_english_text(): void
    {
        $admin = User::where('username', 'admin')->first();

        // Buat 15 user untuk memicu paginasi (10 per halaman)
        User::factory()->count(15)->create([
            'role' => 'karyawan',
        ]);

        $response = $this->actingAs($admin)->get('/admin/user');
        $response->assertStatus(200);

        // Pastikan info paginasi lokal muncul
        $response->assertSee('Menampilkan 1 - 10 dari total');

        // Pastikan navigasi paginasi Bootstrap 5 modern muncul
        $response->assertSee('pagination pagination-sm', false);
        $response->assertSee('bi-chevron-left', false);
        $response->assertSee('bi-chevron-right', false);

        // Pastikan TIDAK ADA teks duplikat bahasa Inggris bawaan Tailwind/Laravel
        $response->assertDontSee('Showing 1 to');
        $response->assertDontSee('&laquo; Previous', false);
        $response->assertDontSee('Next &raquo;', false);
    }
}
