<?php

namespace Tests\Feature;

use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\Laporan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubBagianFilterWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $supervisor;

    protected User $karyawan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create([
            'name' => 'Admin KPU',
            'username' => 'admin_kpu',
            'nip' => '197001012000011001',
        ]);

        $this->supervisor = User::factory()->supervisor()->create([
            'name' => 'Kasubag Teknis',
            'username' => 'spv_teknis',
            'nip' => '197001022000011002',
        ]);

        $this->karyawan = User::factory()->create([
            'role' => 'karyawan',
            'name' => 'Staf Pelaksana',
            'username' => 'staf_pelaksana',
            'nip' => '199001012015011002',
        ]);
    }

    public function test_admin_can_create_and_update_jabatan_with_sub_bagian(): void
    {
        // 1. Create Jabatan with Sub Bagian
        $responseCreate = $this->actingAs($this->admin)->post('/admin/jabatan', [
            'nama_jabatan' => 'Kasubag Teknis Penyelenggaraan Pemilu',
            'sub_bagian' => 'Teknis',
        ]);

        $responseCreate->assertRedirect('/admin/jabatan');
        $this->assertDatabaseHas('jabatans', [
            'nama_jabatan' => 'Kasubag Teknis Penyelenggaraan Pemilu',
            'sub_bagian' => 'Teknis',
        ]);

        $jabatan = Jabatan::where('nama_jabatan', 'Kasubag Teknis Penyelenggaraan Pemilu')->first();

        // 2. Update Jabatan Sub Bagian
        $responseUpdate = $this->actingAs($this->admin)->put("/admin/jabatan/{$jabatan->id}", [
            'nama_jabatan' => 'Kasubag Teknis & Hupmas',
            'sub_bagian' => 'Teknis',
        ]);

        $responseUpdate->assertRedirect('/admin/jabatan');
        $this->assertDatabaseHas('jabatans', [
            'id' => $jabatan->id,
            'nama_jabatan' => 'Kasubag Teknis & Hupmas',
            'sub_bagian' => 'Teknis',
        ]);
    }

    public function test_admin_can_create_and_update_karyawan_with_sub_bagian(): void
    {
        $jabatan = Jabatan::factory()->create([
            'nama_jabatan' => 'Pranata Komputer',
            'sub_bagian' => 'Rendatin',
        ]);

        // 1. Create with explicit sub_bagian
        $responseCreate = $this->actingAs($this->admin)->post('/admin/karyawan', [
            'nip' => '197001162000011016',
            'nama' => 'Pegawai Contoh 16',
            'jabatan_id' => $jabatan->id,
            'sub_bagian' => 'Rendatin',
        ]);

        $responseCreate->assertRedirect('/admin/karyawan');
        $this->assertDatabaseHas('karyawans', [
            'nip' => '197001162000011016',
            'sub_bagian' => 'Rendatin',
        ]);

        $karyawan = Karyawan::where('nip', '197001162000011016')->first();

        // 2. Update Karyawan sub_bagian
        $responseUpdate = $this->actingAs($this->admin)->put("/admin/karyawan/{$karyawan->id}", [
            'nip' => '197001162000011016',
            'nama' => 'Pegawai Contoh 16',
            'jabatan_id' => $jabatan->id,
            'sub_bagian' => 'Teknis',
        ]);

        $responseUpdate->assertRedirect('/admin/karyawan');
        $this->assertDatabaseHas('karyawans', [
            'id' => $karyawan->id,
            'sub_bagian' => 'Teknis',
        ]);
    }

    public function test_karyawan_inherits_jabatan_sub_bagian_when_null(): void
    {
        $jabatan = Jabatan::factory()->create([
            'nama_jabatan' => 'Kasubag KUL',
            'sub_bagian' => 'KUL',
        ]);

        $responseCreate = $this->actingAs($this->admin)->post('/admin/karyawan', [
            'nip' => '197001102000011010',
            'nama' => 'Pegawai Contoh 10',
            'jabatan_id' => $jabatan->id,
            'sub_bagian' => '', // Empty string in form
        ]);

        $responseCreate->assertRedirect('/admin/karyawan');

        $karyawan = Karyawan::where('nip', '197001102000011010')->first();
        $this->assertEquals('KUL', $karyawan->effective_sub_bagian);
    }

    public function test_admin_can_filter_karyawan_by_sub_bagian(): void
    {
        $jabatanTeknis = Jabatan::factory()->create(['sub_bagian' => 'Teknis']);
        $jabatanKul = Jabatan::factory()->create(['sub_bagian' => 'KUL']);

        $pegawaiTeknis = Karyawan::factory()->create([
            'nama' => 'Budi Staf Teknis',
            'jabatan_id' => $jabatanTeknis->id,
            'sub_bagian' => 'Teknis',
        ]);

        $pegawaiKul = Karyawan::factory()->create([
            'nama' => 'Siti Staf KUL',
            'jabatan_id' => $jabatanKul->id,
            'sub_bagian' => 'KUL',
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/karyawan?sub_bagian=Teknis');

        $response->assertOk();
        $response->assertSee('Budi Staf Teknis');
        $response->assertDontSee('Siti Staf KUL');
    }

    public function test_supervisor_can_filter_approval_requests_by_sub_bagian(): void
    {
        $jabatanTeknis = Jabatan::factory()->create(['sub_bagian' => 'Teknis']);
        $jabatanKul = Jabatan::factory()->create(['sub_bagian' => 'KUL']);

        $userTeknis = User::factory()->create(['role' => 'karyawan', 'nip' => '199101012015011001', 'name' => 'Pegawai Divisi Teknis']);
        $userKul = User::factory()->create(['role' => 'karyawan', 'nip' => '199202022016022002', 'name' => 'Pegawai Divisi KUL']);

        Karyawan::factory()->create([
            'nip' => $userTeknis->nip,
            'nama' => $userTeknis->name,
            'jabatan_id' => $jabatanTeknis->id,
            'sub_bagian' => 'Teknis',
        ]);

        Karyawan::factory()->create([
            'nip' => $userKul->nip,
            'nama' => $userKul->name,
            'jabatan_id' => $jabatanKul->id,
            'sub_bagian' => 'KUL',
        ]);

        // Create 1 pending request for Teknis
        Laporan::factory()->tidakHadir()->pending()->create([
            'user_id' => $userTeknis->id,
            'nip' => $userTeknis->nip,
            'nama' => $userTeknis->name,
            'keterangan' => 'Cuti Tahunan',
        ]);

        // Create 1 pending request for KUL
        Laporan::factory()->tidakHadir()->pending()->create([
            'user_id' => $userKul->id,
            'nip' => $userKul->nip,
            'nama' => $userKul->name,
            'keterangan' => 'Izin Menikah',
        ]);

        // Filter approval for Teknis
        $responseTeknis = $this->actingAs($this->supervisor)->get('/approval?status=pending&sub_bagian=Teknis');

        $responseTeknis->assertOk();
        $this->assertCount(1, $responseTeknis->viewData('laporans'));
        $this->assertEquals('Pegawai Divisi Teknis', $responseTeknis->viewData('laporans')->first()->nama);
        $this->assertEquals(1, $responseTeknis->viewData('counts')['pending']);

        // Filter approval for KUL
        $responseKul = $this->actingAs($this->supervisor)->get('/approval?status=pending&sub_bagian=KUL');

        $responseKul->assertOk();
        $this->assertCount(1, $responseKul->viewData('laporans'));
        $this->assertEquals('Pegawai Divisi KUL', $responseKul->viewData('laporans')->first()->nama);
        $this->assertEquals(1, $responseKul->viewData('counts')['pending']);

        // Verify JSON response carries sub_bagian counts accurately
        $responseJson = $this->actingAs($this->supervisor)->getJson('/approval?status=pending&sub_bagian=Teknis');

        $responseJson->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('counts.pending', 1)
            ->assertJsonPath('counts.total', 1);
    }

    public function test_supervisor_and_admin_can_filter_monitoring_by_sub_bagian(): void
    {
        $jabatanRendatin = Jabatan::factory()->create(['sub_bagian' => 'Rendatin']);
        $jabatanHukum = Jabatan::factory()->create(['sub_bagian' => 'Hukum & SDM']);

        $userRendatin = User::factory()->create(['role' => 'karyawan', 'nip' => '199303032017031003', 'name' => 'Staf Rendatin']);
        $userHukum = User::factory()->create(['role' => 'karyawan', 'nip' => '199404042018042004', 'name' => 'Staf Hukum SDM']);

        Karyawan::factory()->create([
            'nip' => $userRendatin->nip,
            'nama' => $userRendatin->name,
            'jabatan_id' => $jabatanRendatin->id,
            'sub_bagian' => 'Rendatin',
        ]);

        Karyawan::factory()->create([
            'nip' => $userHukum->nip,
            'nama' => $userHukum->name,
            'jabatan_id' => $jabatanHukum->id,
            'sub_bagian' => 'Hukum & SDM',
        ]);

        Laporan::factory()->create([
            'tipe' => 'hadir',
            'user_id' => $userRendatin->id,
            'nip' => $userRendatin->nip,
            'nama' => $userRendatin->name,
            'uraian' => 'Pengolahan data rekapitulasi DPT',
        ]);

        Laporan::factory()->create([
            'tipe' => 'hadir',
            'user_id' => $userHukum->id,
            'nip' => $userHukum->nip,
            'nama' => $userHukum->name,
            'uraian' => 'Penyusunan telaah hukum regulasi pemilu',
        ]);

        // Filter monitoring by Rendatin
        $response = $this->actingAs($this->supervisor)->get('/monitoring?sub_bagian=Rendatin');

        $response->assertOk();
        $this->assertCount(1, $response->viewData('laporans'));
        $this->assertEquals('Staf Rendatin', $response->viewData('laporans')->first()->nama);

        // Test Excel export with sub_bagian filter
        $responseExcel = $this->actingAs($this->supervisor)->get('/monitoring/export-excel?sub_bagian=Rendatin');
        $responseExcel->assertOk();
        $this->assertEquals(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $responseExcel->headers->get('content-type')
        );
    }
}
