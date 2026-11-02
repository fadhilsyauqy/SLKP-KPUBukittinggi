<?php

namespace Tests\Feature;

use App\Http\Controllers\MonitoringController;
use App\Models\Laporan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisualAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected User $supervisor;

    protected User $admin;

    protected User $karyawan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->supervisor = User::where('username', 'supervisor')->first();
        $this->admin = User::where('username', 'admin')->first();
        $this->karyawan = User::where('username', 'karyawan')->first();
    }

    public function test_monitoring_view_renders_chart_canvas_and_scripts(): void
    {
        $response = $this->actingAs($this->supervisor)->get('/monitoring');

        $response->assertStatus(200);
        $response->assertSee('trendChart');
        $response->assertSee('absenceChart');
        $response->assertSee('divisionChart');
        $response->assertSee('chart.umd.min.js');
        $response->assertSee('Tren Presensi Harian Pegawai');
        $response->assertSee('Komposisi Ketidakhadiran');
        $response->assertSee('Rekapitulasi Presensi per Subbagian / Divisi Kerja');
    }

    public function test_monitoring_json_includes_chart_analytics_structure(): void
    {
        $response = $this->actingAs($this->supervisor)->getJson('/monitoring');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'statistik',
            'charts' => [
                'daily_trend' => ['labels', 'dates', 'hadir', 'tidak_hadir'],
                'absence_distribution' => ['labels', 'data', 'colors'],
                'division_distribution' => ['labels', 'hadir', 'tidak_hadir'],
            ],
            'data',
        ]);
    }

    public function test_monitoring_charts_endpoint_returns_chart_data(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/monitoring/charts');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'charts' => [
                'daily_trend',
                'absence_distribution',
                'division_distribution',
            ],
        ]);
    }

    public function test_chart_analytics_aggregates_hadir_and_tidak_hadir_accurately(): void
    {
        $today = Carbon::today()->format('Y-m-d');

        // Buat data hadir dan tidak hadir hari ini
        Laporan::factory()->count(3)->create([
            'tipe' => 'hadir',
            'tanggal' => $today,
            'jabatan' => 'Kasubag Keuangan, Umum dan Logistik',
        ]);

        Laporan::factory()->tidakHadir()->count(2)->create([
            'tipe' => 'tidak_hadir',
            'keterangan' => 'Cuti',
            'tanggal' => $today,
            'jabatan' => 'Kasubag Teknis dan Hukum',
        ]);

        Laporan::factory()->tidakHadir()->count(1)->create([
            'tipe' => 'tidak_hadir',
            'keterangan' => 'Sakit',
            'tanggal' => $today,
            'jabatan' => 'Ahli Pertama - Pranata Komputer',
        ]);

        $response = $this->actingAs($this->supervisor)->getJson('/monitoring/charts');
        $response->assertStatus(200);

        $charts = $response->json('charts');

        // 1. Verifikasi Daily Trend
        $todayIndex = array_search($today, $charts['daily_trend']['dates']);
        $this->assertNotFalse($todayIndex);
        $this->assertEquals(3, $charts['daily_trend']['hadir'][$todayIndex]);
        $this->assertEquals(3, $charts['daily_trend']['tidak_hadir'][$todayIndex]); // 2 Cuti + 1 Sakit

        // 2. Verifikasi Komposisi Ketidakhadiran
        $cutiIndex = array_search('Cuti', $charts['absence_distribution']['labels']);
        $this->assertNotFalse($cutiIndex);
        $this->assertGreaterThanOrEqual(2, $charts['absence_distribution']['data'][$cutiIndex]);

        $sakitIndex = array_search('Sakit', $charts['absence_distribution']['labels']);
        $this->assertNotFalse($sakitIndex);
        $this->assertGreaterThanOrEqual(1, $charts['absence_distribution']['data'][$sakitIndex]);

        // 3. Verifikasi Divisi KPU
        $keuanganIndex = array_search('Keuangan & Logistik', $charts['division_distribution']['labels']);
        $this->assertNotFalse($keuanganIndex);
        $this->assertGreaterThanOrEqual(3, $charts['division_distribution']['hadir'][$keuanganIndex]);

        $teknisIndex = array_search('Teknis & Hupmas', $charts['division_distribution']['labels']);
        $this->assertNotFalse($teknisIndex);
        $this->assertGreaterThanOrEqual(2, $charts['division_distribution']['tidak_hadir'][$teknisIndex]);

        $perencanaanIndex = array_search('Perencanaan & Data', $charts['division_distribution']['labels']);
        $this->assertNotFalse($perencanaanIndex);
        $this->assertGreaterThanOrEqual(1, $charts['division_distribution']['tidak_hadir'][$perencanaanIndex]);
    }

    public function test_division_mapping_helper(): void
    {
        $controller = app(MonitoringController::class);

        $this->assertEquals('Keuangan & Logistik', $controller->determineDivision('Kasubag Keuangan, Umum dan Logistik'));
        $this->assertEquals('Keuangan & Logistik', $controller->determineDivision('Pengelola Pengadaan Barang/Jasa'));
        $this->assertEquals('Teknis & Hupmas', $controller->determineDivision('Kasubag Teknis dan Hukum'));
        $this->assertEquals('Teknis & Hupmas', $controller->determineDivision('Penelaah Teknis Kebijakan'));
        $this->assertEquals('Hukum & SDM', $controller->determineDivision('Kasubag Parhubmas dan SDM'));
        $this->assertEquals('Hukum & SDM', $controller->determineDivision('Penyusun Materi Hukum dan Perundang-undangan'));
        $this->assertEquals('Perencanaan & Data', $controller->determineDivision('Kasubag Perencanaan Data dan Informasi'));
        $this->assertEquals('Perencanaan & Data', $controller->determineDivision('Ahli Pertama - Pranata Komputer'));
        $this->assertEquals('Sekretariat & Umum', $controller->determineDivision('Sekretaris KPU'));
        $this->assertEquals('Sekretariat & Umum', $controller->determineDivision('Staff Pelaksana'));
        $this->assertEquals('Sekretariat & Umum', $controller->determineDivision(null));
    }
}
