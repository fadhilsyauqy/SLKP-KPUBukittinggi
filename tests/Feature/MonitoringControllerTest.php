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

    public function test_admin_and_supervisor_can_download_excel_matriks_presensi_bulanan(): void
    {
        $admin = User::where('username', 'admin')->first();
        $supervisor = User::where('username', 'supervisor')->first();

        // Admin download matriks default (bulan berjalan)
        $responseAdmin = $this->actingAs($admin)->get('/monitoring/export-matriks');
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('matriks-presensi-asn-kpu-bukittinggi-', $responseAdmin->headers->get('content-disposition'));

        // Supervisor download matriks bulan spesifik
        $responseSupervisor = $this->actingAs($supervisor)->get('/monitoring/export-matriks?bulan=11&tahun=2026');
        $responseSupervisor->assertStatus(200);
        $responseSupervisor->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('matriks-presensi-asn-kpu-bukittinggi-2026-11.xlsx', $responseSupervisor->headers->get('content-disposition'));
    }

    public function test_matriks_presensi_bulanan_aggregates_hadir_and_tidak_hadir_accurately(): void
    {
        $admin = User::where('username', 'admin')->first();

        // Buat data kehadiran dan ketidakhadiran dalam bulan November 2026
        Laporan::create([
            'nip' => '197001012000011001',
            'nama' => 'Nama Pejabat',
            'jabatan' => 'Sekretaris KPU',
            'tipe' => 'hadir',
            'tanggal' => '2026-11-02',
            'jam_masuk' => '07:30',
            'jam_keluar' => '16:00',
            'uraian' => 'Rapat pleno rutin',
            'output' => '1 Berita Acara',
            'status_approval' => 'approved',
        ]);

        Laporan::create([
            'nip' => '197001012000011001',
            'nama' => 'Nama Pejabat',
            'jabatan' => 'Sekretaris KPU',
            'tipe' => 'tidak_hadir',
            'keterangan' => 'Cuti',
            'tanggal' => '2026-11-03',
            'tanggal_selesai' => '2026-11-05',
            'lama_hari' => '3 Hari',
            'alasan' => 'Cuti tahunan',
            'status_approval' => 'approved',
        ]);

        Laporan::create([
            'nip' => '197001012000011001',
            'nama' => 'Nama Pejabat',
            'jabatan' => 'Sekretaris KPU',
            'tipe' => 'tidak_hadir',
            'keterangan' => 'Perjadin',
            'tanggal' => '2026-11-06',
            'tanggal_selesai' => '2026-11-06',
            'lama_hari' => '1 Hari',
            'alasan' => 'Dinas luar KPU Provinsi',
            'status_approval' => 'approved',
        ]);

        $response = $this->actingAs($admin)->get('/monitoring/export-matriks?bulan=11&tahun=2026');
        $response->assertStatus(200);

        // Ambil isi binary spreadsheet dan periksa struktur OpenXML di dalamnya
        $content = $response->streamedContent();
        $this->assertNotEmpty($content);

        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_test_');
        file_put_contents($tmpFile, $content);

        $zip = new \ZipArchive;
        $opened = $zip->open($tmpFile);
        $this->assertTrue($opened === true);

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $sharedStrings = $zip->getFromName('xl/sharedStrings.xml') ?: '';
        $stylesXml = $zip->getFromName('xl/styles.xml') ?: '';
        $zip->close();
        unlink($tmpFile);

        // Pastikan XML memuat konten matriks dan penanda styling
        $combinedXml = $sheetXml.' '.$sharedStrings.' '.$stylesXml;
        $this->assertStringContainsString('Nama Pejabat', $combinedXml);
        $this->assertStringContainsString('197001012000011001', $combinedXml);
        $this->assertStringContainsString('MATRIKS REKAPITULASI PRESENSI BULANAN PEGAWAI', $combinedXml);
        $this->assertStringContainsString('Total Hadir (H)', $combinedXml);
        $this->assertStringContainsString('Cuti (C)', $combinedXml);
        $this->assertStringContainsString('Dinas Luar (DL)', $combinedXml);

        // Periksa styling abu-abu untuk akhir pekan pada styles.xml (FFE5E7EB)
        $this->assertStringContainsString('E5E7EB', $combinedXml);
    }

    public function test_matriks_presensi_bulanan_respects_sub_bagian_filter(): void
    {
        $supervisor = User::where('username', 'supervisor')->first();

        $response = $this->actingAs($supervisor)->get('/monitoring/export-matriks?bulan=11&tahun=2026&sub_bagian=Teknis');
        $response->assertStatus(200);
        $this->assertStringContainsString('matriks-presensi-asn-kpu-bukittinggi-teknis-2026-11.xlsx', $response->headers->get('content-disposition'));
    }

    public function test_monitoring_view_renders_export_matriks_button(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get('/monitoring');
        $response->assertStatus(200);
        $response->assertSee('Export Matriks Bulanan (Excel)');
        $response->assertSee(route('monitoring.export.matriks'));
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

        // Karyawan mengakses export matriks bulanan ASN
        $this->actingAs($karyawan)->get('/monitoring/export-matriks')->assertStatus(403);
    }

    public function test_guest_cannot_download_excel_and_is_redirected_to_login(): void
    {
        $this->get('/monitoring/export-excel')->assertRedirect('/login');
        $this->get('/monitoring/export-excel/hadir')->assertRedirect('/login');
        $this->get('/monitoring/export-excel/tidak-hadir')->assertRedirect('/login');
        $this->get('/monitoring/export-matriks')->assertRedirect('/login');
    }
}
