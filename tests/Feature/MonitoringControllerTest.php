<?php

namespace Tests\Feature;

use App\Models\Laporan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_supervisor_and_admin_can_view_monitoring(): void
    {
        $admin = User::where('username', 'admin')->first();
        $supervisor = User::where('username', 'supervisor')->first();
        $karyawan = User::where('username', 'karyawan')->first();

        // Admin & Supervisor boleh akses
        $this->actingAs($admin)->get('/monitoring')->assertStatus(200)->assertSee('Monitoring Kehadiran & Kinerja Pegawai');
        $this->actingAs($supervisor)->get('/monitoring')->assertStatus(200)->assertSee('Monitoring Kehadiran & Kinerja Pegawai');

        // Karyawan ditolak
        $this->actingAs($karyawan)->get('/monitoring')->assertStatus(403);
    }

    public function test_monitoring_filters_and_search(): void
    {
        $admin = User::where('username', 'admin')->first();

        Laporan::create([
            'nip' => '197001012000011001',
            'nama' => 'Nama Pejabat',
            'jabatan' => 'Sekretaris KPU',
            'tipe' => 'hadir',
            'tanggal' => '2026-11-02',
            'jam_masuk' => '07:30',
            'jam_keluar' => '16:00',
            'uraian' => 'Koordinasi komisioner terkait logistik pemilu',
            'output' => '1 Berita Acara Rapat',
        ]);

        $response = $this->actingAs($admin)->get('/monitoring?search=logistik');
        $response->assertStatus(200);
        $response->assertSee('Koordinasi komisioner terkait logistik pemilu');
    }

    public function test_monitoring_cetak_view(): void
    {
        $supervisor = User::where('username', 'supervisor')->first();

        $response = $this->actingAs($supervisor)->get('/monitoring/cetak');
        $response->assertStatus(200);
        $response->assertSee('REKAPITULASI LAPORAN KINERJA & PRESENSI HARIAN PEGAWAI');
    }
}
