<?php

namespace Tests\Feature;

use App\Models\HariLibur;
use App\Models\Laporan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HariLiburTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_view_hari_libur_index_and_stats(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get('/admin/hari-libur?tahun=2026');

        $response->assertStatus(200);
        $response->assertSee('Master Kalender Hari Libur Nasional &amp; Cuti Bersama', false);
        $response->assertSee('Tahun Baru 2026 Masehi');
        $response->assertSee('Cuti Bersama Hari Raya Idul Fitri 1447 H');
        $response->assertSee('Libur Nasional');
        $response->assertSee('Cuti Bersama');
    }

    public function test_admin_can_filter_hari_libur_by_type_and_search(): void
    {
        $admin = User::where('username', 'admin')->first();

        // Filter cuti bersama saja
        $responseCuti = $this->actingAs($admin)->get('/admin/hari-libur?tahun=2026&tipe=cuti_bersama');
        $responseCuti->assertStatus(200);
        $responseCuti->assertSee('Cuti Bersama Hari Raya Idul Fitri');
        $responseCuti->assertDontSee('Hari Buruh Internasional');

        // Filter pencarian teks
        $responseSearch = $this->actingAs($admin)->get('/admin/hari-libur?search=Buruh');
        $responseSearch->assertStatus(200);
        $responseSearch->assertSee('Hari Buruh Internasional');
        $responseSearch->assertDontSee('Cuti Bersama Hari Raya Idul Fitri');
    }

    public function test_admin_can_create_national_holiday_and_cuti_bersama(): void
    {
        $admin = User::where('username', 'admin')->first();

        $payload = [
            'tanggal' => '2026-10-28',
            'keterangan' => 'Hari Sumpah Pemuda',
            'is_cuti_bersama' => '0',
        ];

        $response = $this->actingAs($admin)->post('/admin/hari-libur', $payload);

        $response->assertRedirect('/admin/hari-libur?tahun=2026');
        $this->assertDatabaseHas('hari_liburs', [
            'tanggal' => '2026-10-28',
            'keterangan' => 'Hari Sumpah Pemuda',
            'is_cuti_bersama' => false,
        ]);

        // Tambah Cuti Bersama
        $payloadCuti = [
            'tanggal' => '2026-10-29',
            'keterangan' => 'Cuti Bersama Sumpah Pemuda',
            'is_cuti_bersama' => '1',
        ];

        $responseCuti = $this->actingAs($admin)->post('/admin/hari-libur', $payloadCuti);
        $responseCuti->assertRedirect('/admin/hari-libur?tahun=2026');

        $this->assertDatabaseHas('hari_liburs', [
            'tanggal' => '2026-10-29',
            'keterangan' => 'Cuti Bersama Sumpah Pemuda',
            'is_cuti_bersama' => true,
        ]);
    }

    public function test_admin_cannot_create_duplicate_holiday_date(): void
    {
        $admin = User::where('username', 'admin')->first();

        // Tanggal 2026-08-17 sudah ada di seeder
        $response = $this->actingAs($admin)->post('/admin/hari-libur', [
            'tanggal' => '2026-08-17',
            'keterangan' => 'Hari Kemerdekaan RI Duplikat',
        ]);

        $response->assertSessionHasErrors(['tanggal']);
        $this->assertEquals(1, HariLibur::where('tanggal', '2026-08-17')->count());
    }

    public function test_admin_can_update_holiday(): void
    {
        $admin = User::where('username', 'admin')->first();

        $libur = HariLibur::create([
            'tanggal' => '2026-11-10',
            'keterangan' => 'Hari Pahlawan',
            'is_cuti_bersama' => false,
        ]);

        $response = $this->actingAs($admin)->put("/admin/hari-libur/{$libur->id}", [
            'tanggal' => '2026-11-10',
            'keterangan' => 'Hari Pahlawan Nasional',
            'is_cuti_bersama' => '1',
        ]);

        $response->assertRedirect('/admin/hari-libur?tahun=2026');
        $this->assertDatabaseHas('hari_liburs', [
            'id' => $libur->id,
            'keterangan' => 'Hari Pahlawan Nasional',
            'is_cuti_bersama' => true,
        ]);
    }

    public function test_admin_can_delete_holiday(): void
    {
        $admin = User::where('username', 'admin')->first();

        $libur = HariLibur::create([
            'tanggal' => '2026-11-20',
            'keterangan' => 'Libur Khusus Pilkada',
            'is_cuti_bersama' => false,
        ]);

        $response = $this->actingAs($admin)->delete("/admin/hari-libur/{$libur->id}");

        $response->assertRedirect('/admin/hari-libur?tahun=2026');
        $this->assertDatabaseMissing('hari_liburs', [
            'id' => $libur->id,
        ]);
    }

    public function test_non_admin_cannot_access_or_modify_hari_libur(): void
    {
        $karyawan = User::where('username', 'karyawan')->first();
        $supervisor = User::where('username', 'supervisor')->first();

        $this->actingAs($karyawan)->get('/admin/hari-libur')->assertStatus(403);
        $this->actingAs($supervisor)->get('/admin/hari-libur')->assertStatus(403);

        $this->actingAs($karyawan)->post('/admin/hari-libur', [
            'tanggal' => '2026-09-01',
            'keterangan' => 'Libur Ilegal',
        ])->assertStatus(403);

        auth()->logout();
        $this->get('/admin/hari-libur')->assertRedirect('/login');
    }

    public function test_hari_libur_model_count_working_days_excludes_weekends_and_holidays(): void
    {
        // November 2026:
        // 2026-11-02 (Senin) s/d 2026-11-06 (Jumat) = 5 hari kerja normal.
        // Tambahkan hari libur nasional pada hari Rabu, 2026-11-04
        HariLibur::create([
            'tanggal' => '2026-11-04',
            'keterangan' => 'Hari Libur Uji Coba',
            'is_cuti_bersama' => false,
        ]);

        $start = Carbon::parse('2026-11-02');
        $end = Carbon::parse('2026-11-06');

        $workingDays = HariLibur::countWorkingDays($start, $end);

        // 5 hari kerja dikurangi 1 hari libur nasional = 4 hari kerja efektif
        $this->assertEquals(4, $workingDays);

        // Rentang melewati akhir pekan: 2026-11-06 (Jumat) s/d 2026-11-09 (Senin)
        // Jumat (+1), Sabtu (0), Minggu (0), Senin (+1) = 2 hari kerja
        $weekendSpan = HariLibur::countWorkingDays('2026-11-06', '2026-11-09');
        $this->assertEquals(2, $weekendSpan);
    }

    public function test_durasi_tidak_hadir_api_endpoint_calculates_working_days_for_cuti_and_calendar_for_others(): void
    {
        $karyawan = User::where('username', 'karyawan')->first();

        // Daftarkan hari libur di tengah minggu (Rabu, 2026-11-04)
        HariLibur::create([
            'tanggal' => '2026-11-04',
            'keterangan' => 'Libur Nasional',
            'is_cuti_bersama' => false,
        ]);

        // Cuti: mengecualikan akhir pekan dan libur nasional (Senin-Jumat, 5 hari kalender, 1 libur = 4 hari kerja)
        $responseCuti = $this->actingAs($karyawan)->getJson('/laporan/durasi-tidak-hadir?tanggal_in=2026-11-02&tanggal_out=2026-11-06&keterangan=Cuti');
        $responseCuti->assertStatus(200);
        $responseCuti->assertJson([
            'status' => 'success',
            'data' => [
                'total_kalender' => 5,
                'hari_kerja' => 4,
                'durasi_terhitung' => 4,
                'label' => '4 Hari Kerja',
                'is_workday_exclusion' => true,
                'holidays_count' => 1,
            ],
        ]);

        // Perjadin: menggunakan hari kalender penuh
        $responsePerjadin = $this->actingAs($karyawan)->getJson('/laporan/durasi-tidak-hadir?tanggal_in=2026-11-02&tanggal_out=2026-11-06&keterangan=Perjadin');
        $responsePerjadin->assertStatus(200);
        $responsePerjadin->assertJson([
            'status' => 'success',
            'data' => [
                'total_kalender' => 5,
                'hari_kerja' => 4,
                'durasi_terhitung' => 5,
                'label' => '5 Hari Kalender',
                'is_workday_exclusion' => false,
            ],
        ]);
    }

    public function test_karyawan_submitting_cuti_calculates_duration_excluding_weekends_and_holidays(): void
    {
        $karyawan = User::where('username', 'karyawan')->first();

        // Daftarkan hari libur nasional pada 2026-11-04 (Rabu)
        HariLibur::create([
            'tanggal' => '2026-11-04',
            'keterangan' => 'Libur Nasional Tengah Pekan',
            'is_cuti_bersama' => false,
        ]);

        // Ajukan cuti dari Senin (2026-11-02) sampai Jumat (2026-11-06)
        $response = $this->actingAs($karyawan)->post('/laporan/tidak-hadir', [
            'nip' => $karyawan->nip,
            'nama' => $karyawan->name,
            'jabatan' => 'Staff',
            'keterangan' => 'Cuti',
            'tanggal_in' => '2026-11-02',
            'tanggal_out' => '2026-11-06',
            'alasan' => 'Cuti tahunan keperluan keluarga',
        ]);

        $response->assertRedirect('/laporan/riwayat');

        // Verifikasi lama_hari tersimpan sebagai '4 Hari' (bukan 5 Hari)
        $this->assertDatabaseHas('laporans', [
            'nip' => $karyawan->nip,
            'tipe' => 'tidak_hadir',
            'keterangan' => 'Cuti',
            'lama_hari' => '4 Hari',
        ]);
    }

    public function test_monitoring_matriks_export_excludes_holidays_from_effective_working_days_and_recalculates_percentage(): void
    {
        $admin = User::where('username', 'admin')->first();

        // Bulan November 2026 memiliki 30 hari.
        // Hari kerja normal (Senin-Jumat) di November 2026 adalah 21 hari.
        // Tambahkan 2 hari libur nasional di November 2026:
        HariLibur::create([
            'tanggal' => '2026-11-04',
            'keterangan' => 'Hari Libur Nasional 1',
            'is_cuti_bersama' => false,
        ]);

        HariLibur::create([
            'tanggal' => '2026-11-12',
            'keterangan' => 'Hari Libur Nasional 2',
            'is_cuti_bersama' => false,
        ]);

        // Hari kerja efektif seharusnya 21 - 2 = 19 Hari!
        for ($day = 2; $day <= 6; $day++) {
            if ($day === 4) {
                continue; // Libur
            }
            $tgl = sprintf('2026-11-%02d', $day);
            Laporan::create([
                'nip' => '197001012000011001',
                'nama' => 'Nama Pejabat',
                'jabatan' => 'Sekretaris KPU',
                'tipe' => 'hadir',
                'tanggal' => $tgl,
                'jam_masuk' => '07:30',
                'jam_keluar' => '16:00',
                'uraian' => 'Tugas kedinasan',
                'output' => 'Laporan',
                'status_approval' => 'approved',
            ]);
        }

        $response = $this->actingAs($admin)->get('/monitoring/export-matriks?bulan=11&tahun=2026');
        $response->assertStatus(200);

        // Periksa dokumen stream
        $content = $response->streamedContent();
        $this->assertNotEmpty($content);

        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_libur_test_');
        file_put_contents($tmpFile, $content);

        $zip = new \ZipArchive;
        $zip->open($tmpFile);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $sharedStrings = $zip->getFromName('xl/sharedStrings.xml') ?: '';
        $zip->close();
        unlink($tmpFile);

        $combinedXml = $sheetXml.' '.$sharedStrings;

        // Pastikan jumlah hari kerja efektif tertulis '19 Hari'
        $this->assertStringContainsString('Hari Kerja Efektif: 19 Hari', $combinedXml);
        $this->assertStringContainsString('Hari Libur Nasional / Cuti Bersama resmi', $combinedXml);
    }
}
