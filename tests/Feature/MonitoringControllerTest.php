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

    public function test_admin_and_supervisor_can_download_excel_laporan_kehadiran(): void
    {
        $admin = User::where('username', 'admin')->first();
        $supervisor = User::where('username', 'supervisor')->first();

        Laporan::create([
            'nip' => '197001012000011001',
            'nama' => 'Nama Pejabat',
            'jabatan' => 'Sekretaris KPU',
            'tipe' => 'hadir',
            'tanggal' => '2026-11-02',
            'jam_masuk' => '07:30',
            'jam_keluar' => '16:00',
            'uraian' => 'Rapat koordinasi pilkada',
            'output' => '1 Dokumen Notula',
        ]);

        // Admin download via shortcut route
        $responseAdmin = $this->actingAs($admin)->get('/monitoring/export-excel/hadir');
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('laporan-kehadiran-kpu-bukittinggi-', $responseAdmin->headers->get('content-disposition'));

        // Supervisor download via query parameter route
        $responseSupervisor = $this->actingAs($supervisor)->get('/monitoring/export-excel?tipe=hadir');
        $responseSupervisor->assertStatus(200);
        $responseSupervisor->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('laporan-kehadiran-kpu-bukittinggi-', $responseSupervisor->headers->get('content-disposition'));
    }

    public function test_admin_and_supervisor_can_download_excel_laporan_tidak_hadir(): void
    {
        $admin = User::where('username', 'admin')->first();
        $supervisor = User::where('username', 'supervisor')->first();

        Laporan::create([
            'nip' => '198502022010012002',
            'nama' => 'Sri Wahyuni, S.Sos',
            'jabatan' => 'Kasubag Teknis Penyelenggaraan Pemilu',
            'tipe' => 'tidak_hadir',
            'keterangan' => 'Cuti',
            'tanggal' => '2026-11-02',
            'tanggal_selesai' => '2026-11-04',
            'lama_hari' => '3 Hari',
            'alasan' => 'Cuti tahunan urusan keluarga',
        ]);

        // Admin download via shortcut route
        $responseAdmin = $this->actingAs($admin)->get('/monitoring/export-excel/tidak-hadir');
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('laporan-tidak-hadir-kpu-bukittinggi-', $responseAdmin->headers->get('content-disposition'));

        // Supervisor download via query param
        $responseSupervisor = $this->actingAs($supervisor)->get('/monitoring/export-excel?tipe=tidak_hadir');
        $responseSupervisor->assertStatus(200);
        $responseSupervisor->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('laporan-tidak-hadir-kpu-bukittinggi-', $responseSupervisor->headers->get('content-disposition'));
    }

    public function test_karyawan_cannot_download_excel_and_receives_403_forbidden(): void
    {
        $karyawan = User::where('username', 'karyawan')->first();

        // Karyawan mengakses export umum
        $this->actingAs($karyawan)->get('/monitoring/export-excel')->assertStatus(403);

        // Karyawan mengakses export hadir
        $this->actingAs($karyawan)->get('/monitoring/export-excel/hadir')->assertStatus(403);
        $this->actingAs($karyawan)->get('/monitoring/export-excel?tipe=hadir')->assertStatus(403);

        // Karyawan mengakses export tidak hadir
        $this->actingAs($karyawan)->get('/monitoring/export-excel/tidak-hadir')->assertStatus(403);
        $this->actingAs($karyawan)->get('/monitoring/export-excel?tipe=tidak_hadir')->assertStatus(403);
    }

    public function test_guest_cannot_download_excel_and_is_redirected_to_login(): void
    {
        $this->get('/monitoring/export-excel')->assertRedirect('/login');
        $this->get('/monitoring/export-excel/hadir')->assertRedirect('/login');
        $this->get('/monitoring/export-excel/tidak-hadir')->assertRedirect('/login');
    }
}
