<?php

namespace Tests\Feature;

use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\Laporan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RbacAndGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guest_is_redirected_to_login_when_accessing_protected_routes(): void
    {
        $this->get('/monitoring')->assertRedirect('/login');
        $this->get('/admin/karyawan')->assertRedirect('/login');
        $this->get('/laporan/riwayat')->assertRedirect('/login');
    }

    public function test_admin_can_access_monitoring_and_admin_master_routes(): void
    {
        $admin = User::where('username', 'admin')->first();

        $this->actingAs($admin)->get('/monitoring')->assertStatus(200);
        $this->actingAs($admin)->get('/admin/karyawan')->assertStatus(200);
        $this->actingAs($admin)->get('/admin/jabatan')->assertStatus(200);
        $this->actingAs($admin)->get('/admin/user')->assertStatus(200);
    }

    public function test_supervisor_can_access_monitoring_and_laporan_but_not_admin_routes(): void
    {
        $supervisor = User::where('username', 'supervisor')->first();

        $this->actingAs($supervisor)->get('/monitoring')->assertStatus(200);
        $this->actingAs($supervisor)->get('/laporan/riwayat')->assertStatus(200);
        $this->actingAs($supervisor)->get('/laporan/hadir')->assertStatus(200);

        // Supervisor dilarang akses CRUD master data admin
        $this->actingAs($supervisor)->get('/admin/karyawan')->assertStatus(403);
        $this->actingAs($supervisor)->get('/admin/jabatan')->assertStatus(403);
        $this->actingAs($supervisor)->get('/admin/user')->assertStatus(403);
    }

    public function test_karyawan_can_access_laporan_routes_but_not_monitoring_or_admin_routes(): void
    {
        $karyawan = User::where('username', 'karyawan')->first();

        $this->actingAs($karyawan)->get('/laporan/riwayat')->assertStatus(200);
        $this->actingAs($karyawan)->get('/laporan/hadir')->assertStatus(200);
        $this->actingAs($karyawan)->get('/laporan/tidak-hadir')->assertStatus(200);

        // Karyawan dilarang akses monitoring dan admin master data
        $this->actingAs($karyawan)->get('/monitoring')->assertStatus(403);
        $this->actingAs($karyawan)->get('/admin/karyawan')->assertStatus(403);
    }

    public function test_gate_authorization_rules(): void
    {
        $admin = User::where('username', 'admin')->first();
        $supervisor = User::where('username', 'supervisor')->first();
        $karyawan = User::where('username', 'karyawan')->first();

        // 1. Gate manage-master-data: hanya admin
        $this->assertTrue(Gate::forUser($admin)->allows('manage-master-data'));
        $this->assertFalse(Gate::forUser($supervisor)->allows('manage-master-data'));
        $this->assertFalse(Gate::forUser($karyawan)->allows('manage-master-data'));

        // 2. Gate view-monitoring: admin & supervisor
        $this->assertTrue(Gate::forUser($admin)->allows('view-monitoring'));
        $this->assertTrue(Gate::forUser($supervisor)->allows('view-monitoring'));
        $this->assertFalse(Gate::forUser($karyawan)->allows('view-monitoring'));

        // 3. Gate create-laporan: admin, supervisor, karyawan
        $this->assertTrue(Gate::forUser($karyawan)->allows('create-laporan'));
        $this->assertTrue(Gate::forUser($supervisor)->allows('create-laporan'));
        $this->assertTrue(Gate::forUser($admin)->allows('create-laporan'));
    }

    public function test_policies_authorization(): void
    {
        $admin = User::where('username', 'admin')->first();
        $supervisor = User::where('username', 'supervisor')->first();
        $karyawan = User::where('username', 'karyawan')->first();

        $karyawanModel = Karyawan::first();
        $jabatanModel = Jabatan::first();

        // Laporan policy
        $this->assertTrue($admin->can('viewAny', Laporan::class));
        $this->assertTrue($supervisor->can('viewAny', Laporan::class));
        $this->assertFalse($karyawan->can('viewAny', Laporan::class));

        $this->assertTrue($karyawan->can('create', Laporan::class));
        $this->assertTrue($supervisor->can('create', Laporan::class));
        $this->assertTrue($admin->can('create', Laporan::class));

        // Karyawan & Jabatan policy (CRUD master)
        $this->assertTrue($admin->can('create', Karyawan::class));
        $this->assertFalse($supervisor->can('create', Karyawan::class));
        $this->assertFalse($karyawan->can('create', Karyawan::class));

        $this->assertTrue($admin->can('update', $karyawanModel));
        $this->assertFalse($supervisor->can('update', $karyawanModel));

        $this->assertTrue($admin->can('create', Jabatan::class));
        $this->assertFalse($supervisor->can('create', Jabatan::class));
    }
}
