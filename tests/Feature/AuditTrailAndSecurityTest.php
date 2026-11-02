<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Jabatan;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuditTrailAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_activity_logger_service_creates_log_record(): void
    {
        $user = User::where('username', 'admin')->first();

        $log = ActivityLogger::log(
            'TEST_ACTION',
            'TEST_MODULE',
            'Uji coba pencatatan log aktivitas sistem.',
            ['sample_key' => 'sample_value'],
            $user
        );

        $this->assertInstanceOf(ActivityLog::class, $log);
        $this->assertDatabaseHas('activity_logs', [
            'id' => $log->id,
            'user_id' => $user->id,
            'action' => 'TEST_ACTION',
            'module' => 'TEST_MODULE',
            'description' => 'Uji coba pencatatan log aktivitas sistem.',
        ]);
        $this->assertEquals(['sample_key' => 'sample_value'], $log->payload);
    }

    public function test_user_login_and_logout_creates_activity_logs(): void
    {
        $user = User::where('username', 'karyawan')->first();

        // 1. Uji Login
        $response = $this->post('/login', [
            'username' => 'karyawan',
            'password' => 'karyawan123',
        ]);
        $response->assertRedirect('/laporan/riwayat');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'LOGIN',
            'module' => 'AUTH',
        ]);

        // 2. Uji Logout
        $responseLogout = $this->actingAs($user)->post('/logout');
        $responseLogout->assertRedirect('/login');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'LOGOUT',
            'module' => 'AUTH',
        ]);
    }

    public function test_admin_can_access_activity_log_dashboard(): void
    {
        $admin = User::where('username', 'admin')->first();

        ActivityLogger::log('TEST', 'AUTH', 'Contoh log aktivitas', null, $admin);

        $response = $this->actingAs($admin)->get('/admin/activity-log');

        $response->assertStatus(200);
        $response->assertSee('Audit Trail &amp; Rekam Jejak Sistem', false);
        $response->assertSee('Contoh log aktivitas');
    }

    public function test_supervisor_and_karyawan_cannot_access_activity_log(): void
    {
        $supervisor = User::where('username', 'supervisor')->first();
        $karyawan = User::where('username', 'karyawan')->first();

        $this->actingAs($supervisor)->get('/admin/activity-log')->assertStatus(403);
        $this->actingAs($karyawan)->get('/admin/activity-log')->assertStatus(403);
    }

    public function test_guest_is_redirected_to_login_when_accessing_activity_log(): void
    {
        $this->get('/admin/activity-log')->assertRedirect('/login');
    }

    public function test_activity_log_filters_work(): void
    {
        $admin = User::where('username', 'admin')->first();

        ActivityLogger::log('CREATE', 'PEGAWAI', 'Menambah data pegawai Riko', null, $admin);
        ActivityLogger::log('DELETE', 'JABATAN', 'Menghapus jabatan staf', null, $admin);

        // Filter Aksi CREATE
        $responseAction = $this->actingAs($admin)->get('/admin/activity-log?action=CREATE');
        $responseAction->assertStatus(200);
        $responseAction->assertSee('Menambah data pegawai Riko');
        $responseAction->assertDontSee('Menghapus jabatan staf');

        // Filter Modul JABATAN
        $responseModule = $this->actingAs($admin)->get('/admin/activity-log?module=JABATAN');
        $responseModule->assertStatus(200);
        $responseModule->assertSee('Menghapus jabatan staf');
        $responseModule->assertDontSee('Menambah data pegawai Riko');
    }

    public function test_force_password_change_middleware_redirects_user_with_flag(): void
    {
        $user = User::factory()->create([
            'username' => 'user_baru',
            'password' => Hash::make('password123'),
            'role' => 'karyawan',
            'must_change_password' => true,
        ]);

        // Coba akses riwayat laporan saat flag aktif -> dialihkan ke form ubah password
        $response = $this->actingAs($user)->get('/laporan/riwayat');
        $response->assertRedirect(route('password.change'));
        $response->assertSessionHas('warning');

        // Permintaan JSON menerima status 403 dengan instruksi must_change_password
        $responseJson = $this->actingAs($user)->getJson('/laporan/riwayat');
        $responseJson->assertStatus(403);
        $responseJson->assertJson([
            'status' => 'error',
            'must_change_password' => true,
        ]);

        // Dapat mengakses halaman ganti password tanpa dialihkan
        $responseForm = $this->actingAs($user)->get('/password/change');
        $responseForm->assertStatus(200);
        $responseForm->assertSee('Pembaruan Kata Sandi');

        // Dapat melakukan logout tanpa terblokir
        $responseLogout = $this->actingAs($user)->post('/logout');
        $responseLogout->assertRedirect('/login');
    }

    public function test_user_can_successfully_change_password_and_clear_flag(): void
    {
        $user = User::factory()->create([
            'username' => 'pegawai_ganti_pwd',
            'password' => Hash::make('password123'),
            'role' => 'karyawan',
            'must_change_password' => true,
        ]);

        $response = $this->actingAs($user)->post('/password/change', [
            'current_password' => 'password123',
            'password' => 'passwordBaru123',
            'password_confirmation' => 'passwordBaru123',
        ]);

        $response->assertRedirect('/laporan/riwayat');
        $response->assertSessionHas('success');

        // Flag must_change_password ter-reset menjadi false
        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('passwordBaru123', $user->password));

        // Aktivitas ganti password tercatat di activity_logs
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'UPDATE',
            'module' => 'AUTH',
        ]);

        // Akses menu sistem sekarang diizinkan
        $this->actingAs($user)->get('/laporan/riwayat')->assertStatus(200);
    }

    public function test_change_password_validation_rules(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('currentPass123'),
            'must_change_password' => true,
        ]);

        // 1. Password lama salah
        $responseWrongCurrent = $this->actingAs($user)->post('/password/change', [
            'current_password' => 'wrongpass',
            'password' => 'newPassword123',
            'password_confirmation' => 'newPassword123',
        ]);
        $responseWrongCurrent->assertSessionHasErrors('current_password');

        // 2. Konfirmasi password tidak cocok
        $responseMismatch = $this->actingAs($user)->post('/password/change', [
            'current_password' => 'currentPass123',
            'password' => 'newPassword123',
            'password_confirmation' => 'different123',
        ]);
        $responseMismatch->assertSessionHasErrors('password');

        // 3. Password baru sama dengan password lama
        $responseSame = $this->actingAs($user)->post('/password/change', [
            'current_password' => 'currentPass123',
            'password' => 'currentPass123',
            'password_confirmation' => 'currentPass123',
        ]);
        $responseSame->assertSessionHasErrors('password');

        // 4. Password baru kurang dari 6 karakter
        $responseShort = $this->actingAs($user)->post('/password/change', [
            'current_password' => 'currentPass123',
            'password' => '123',
            'password_confirmation' => '123',
        ]);
        $responseShort->assertSessionHasErrors('password');
    }
}
