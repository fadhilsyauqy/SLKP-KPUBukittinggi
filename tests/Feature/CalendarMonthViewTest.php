<?php

namespace Tests\Feature;

use App\Models\HariLibur;
use App\Models\Laporan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarMonthViewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Carbon::setTestNow(Carbon::parse('2026-11-04 09:00:00', 'Asia/Jakarta'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_riwayat_view_renders_ui_toggle_table_and_calendar_buttons(): void
    {
        $karyawan = User::where('username', 'karyawan')->first();

        $response = $this->actingAs($karyawan)->get('/laporan/riwayat');

        $response->assertStatus(200);
        $response->assertSee('id="btnViewTable"', false);
        $response->assertSee('id="btnViewCalendar"', false);
        $response->assertSee('Tampilan Tabel');
        $response->assertSee('Tampilan Kalender');
        $response->assertSee('switchRiwayatView', false);
    }

    public function test_riwayat_renders_calendar_view_when_view_param_is_calendar(): void
    {
        $karyawan = User::where('username', 'karyawan')->first();

        $response = $this->actingAs($karyawan)->get('/laporan/riwayat?view=calendar&bulan=11&tahun=2026');

        $response->assertStatus(200);
        $response->assertSee('id="calendarViewSection"', false);
        $response->assertSee('November 2026');
        $response->assertSee('Senin');
        $response->assertSee('Selasa');
        $response->assertSee('Rabu');
        $response->assertSee('Kamis');
        $response->assertSee('Jumat');
        $response->assertSee('Sabtu');
        $response->assertSee('Minggu');

        // Legend Status Sesuai Kriteria Acceptance
        $response->assertSee('Hadir');
        $response->assertSee('Izin / Cuti / Sakit');
        $response->assertSee('Akhir Pekan / Belum Lapor');
    }

    public function test_calendar_view_color_coding_for_hadir_leave_and_weekend(): void
    {
        $karyawan = User::where('username', 'karyawan')->first();

        // 1. Buat Laporan Hadir pada 04 Nov 2026 (Rabu)
        Laporan::create([
            'user_id' => $karyawan->id,
            'nip' => $karyawan->nip,
            'nama' => $karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tipe' => 'hadir',
            'tanggal' => '2026-11-04',
            'jam_masuk' => '07:30',
            'jam_keluar' => '16:00',
            'uraian' => 'Rapat pleno penetapan data pemilih',
            'output' => '1 Berita Acara',
            'status_approval' => 'approved',
            'status_sinkron_google' => true,
        ]);

        // 2. Buat Laporan Izin / Cuti pada 05 Nov s/d 06 Nov 2026 (Kamis - Jumat)
        Laporan::create([
            'user_id' => $karyawan->id,
            'nip' => $karyawan->nip,
            'nama' => $karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tipe' => 'tidak_hadir',
            'keterangan' => 'Cuti Tahunan',
            'tanggal' => '2026-11-05',
            'tanggal_selesai' => '2026-11-06',
            'lama_hari' => '2 hari',
            'alasan' => 'Keperluan keluarga di luar kota',
            'status_approval' => 'approved',
            'status_sinkron_google' => true,
        ]);

        $response = $this->actingAs($karyawan)->get('/laporan/riwayat?view=calendar&bulan=11&tahun=2026');

        $response->assertStatus(200);

        // 🟢 Kotak Hijau untuk Hadir
        $response->assertSee('cal-tile-green', false);

        // 🟡 Kotak Oranye untuk Izin/Cuti
        $response->assertSee('cal-tile-orange', false);
        $response->assertSee('Cuti Tahunan');

        // ⚪ Kotak Abu-abu untuk Weekend & Belum Lapor
        $response->assertSee('cal-tile-weekend', false);
        $response->assertSee('cal-tile-belumlapor', false);
    }

    public function test_calendar_view_displays_national_holiday_color_and_label(): void
    {
        $karyawan = User::where('username', 'karyawan')->first();

        // Buat hari libur nasional pada 10 Nov 2026 (Hari Pahlawan)
        HariLibur::create([
            'tanggal' => '2026-11-10',
            'keterangan' => 'Hari Pahlawan Nasional',
            'is_cuti_bersama' => false,
        ]);

        $response = $this->actingAs($karyawan)->get('/laporan/riwayat?view=calendar&bulan=11&tahun=2026');

        $response->assertStatus(200);
        $response->assertSee('cal-tile-holiday', false);
        $response->assertSee('Hari Pahlawan');
    }

    public function test_calendar_view_renders_modal_detail_container(): void
    {
        $karyawan = User::where('username', 'karyawan')->first();

        $response = $this->actingAs($karyawan)->get('/laporan/riwayat?view=calendar');

        $response->assertStatus(200);
        $response->assertSee('id="modalDetailKalender"', false);
        $response->assertSee('id="modalDetailKalenderLabel"', false);
        $response->assertSee('id="modalDetailBody"', false);
        $response->assertSee('openCalendarModal', false);
    }

    public function test_riwayat_json_endpoint_returns_structured_calendar_data(): void
    {
        $karyawan = User::where('username', 'karyawan')->first();

        Laporan::create([
            'user_id' => $karyawan->id,
            'nip' => $karyawan->nip,
            'nama' => $karyawan->name,
            'jabatan' => 'Penelaah Teknis Kebijakan',
            'tipe' => 'hadir',
            'tanggal' => '2026-11-04',
            'jam_masuk' => '07:30',
            'jam_keluar' => '16:00',
            'uraian' => 'Verifikasi dokumen administrasi',
            'output' => '1 Berkas',
            'status_approval' => 'approved',
            'status_sinkron_google' => true,
        ]);

        $response = $this->actingAs($karyawan)->getJson('/laporan/riwayat?bulan=11&tahun=2026');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'has_reported_today' => true,
        ]);

        $response->assertJsonStructure([
            'status',
            'has_reported_today',
            'calendar' => [
                'nav' => [
                    'current_bulan',
                    'current_tahun',
                    'nama_bulan',
                    'prev_bulan',
                    'prev_tahun',
                    'next_bulan',
                    'next_tahun',
                ],
                'days',
                'stats' => [
                    'total_hadir',
                    'total_izin',
                    'total_belum_lapor',
                    'total_hari_kerja',
                ],
            ],
        ]);

        $this->assertEquals(1, $response->json('calendar.stats.total_hadir'));
    }

    public function test_calendar_navigation_updates_month_and_year_scope(): void
    {
        $karyawan = User::where('username', 'karyawan')->first();

        $response = $this->actingAs($karyawan)->get('/laporan/riwayat?view=calendar&bulan=12&tahun=2026');

        $response->assertStatus(200);
        $response->assertSee('Desember 2026');
    }
}
