<?php

namespace Tests\Feature;

use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Shuchkin\SimpleXLSXGen;
use Tests\TestCase;

class KaryawanImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $supervisor;

    protected User $karyawanUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'username' => 'admin_test',
        ]);

        $this->supervisor = User::factory()->create([
            'role' => 'supervisor',
            'username' => 'supervisor_test',
        ]);

        $this->karyawanUser = User::factory()->create([
            'role' => 'karyawan',
            'username' => 'karyawan_test',
        ]);
    }

    /**
     * Helper untuk membuat UploadedFile bertipe .xlsx di disk sementara.
     *
     * @param  array<int, array<int, mixed>>  $rows
     */
    protected function createFakeExcelFile(array $rows, string $filename = 'test_karyawan.xlsx'): UploadedFile
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'test_xlsx_');
        $xlsx = SimpleXLSXGen::fromArray($rows);
        $xlsx->saveAs($tempPath);

        return new UploadedFile(
            $tempPath,
            $filename,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }

    /**
     * Helper untuk membuat UploadedFile bertipe .csv di disk sementara.
     *
     * @param  array<int, array<int, mixed>>  $rows
     */
    protected function createFakeCsvFile(array $rows, string $filename = 'test_karyawan.csv', string $delimiter = ','): UploadedFile
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'test_csv_');
        $handle = fopen($tempPath, 'w');
        foreach ($rows as $row) {
            fputcsv($handle, $row, $delimiter);
        }
        fclose($handle);

        return new UploadedFile(
            $tempPath,
            $filename,
            'text/csv',
            null,
            true
        );
    }

    public function test_admin_can_download_template_excel(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.karyawan.template'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('template_karyawan.xlsx', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_non_admin_cannot_download_template(): void
    {
        $this->actingAs($this->supervisor)
            ->get(route('admin.karyawan.template'))
            ->assertStatus(403);

        $this->actingAs($this->karyawanUser)
            ->get(route('admin.karyawan.template'))
            ->assertStatus(403);

        auth()->logout();

        $this->get(route('admin.karyawan.template'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_import_karyawan_from_excel(): void
    {
        $rows = [
            ['No', 'NIP', 'Nama Lengkap', 'Jabatan', 'Sub Bagian'],
            [1, '199201012015011001', 'Fadhil Pratama, S.T.', 'Pengolah Data dan Informasi', 'Rendatin'],
            [2, '199302022016022002', 'Annisa Fitri, S.H.', 'Penelaah Teknis Kebijakan', 'Hukum & SDM'],
        ];

        $file = $this->createFakeExcelFile($rows);

        $response = $this->actingAs($this->admin)->post(route('admin.karyawan.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('admin.karyawan.index'));
        $response->assertSessionHas('success');
        $response->assertSessionHas('import_result');

        $this->assertDatabaseHas('karyawans', [
            'nip' => '199201012015011001',
            'nama' => 'Fadhil Pratama, S.T.',
            'sub_bagian' => 'Rendatin',
            'sort_order' => 5, // Pengolah -> sort_order 5
        ]);

        $this->assertDatabaseHas('karyawans', [
            'nip' => '199302022016022002',
            'nama' => 'Annisa Fitri, S.H.',
            'sub_bagian' => 'Hukum & SDM',
            'sort_order' => 3, // Penelaah -> sort_order 3
        ]);
    }

    public function test_admin_can_import_karyawan_from_csv(): void
    {
        $rows = [
            ['No', 'NIP', 'Nama Lengkap', 'Jabatan', 'Sub Bagian'],
            ['1', '199403032017031003', 'Bambang Sudiro', 'Staf Teknis Pemilu', 'Teknis'],
        ];

        $file = $this->createFakeCsvFile($rows, 'pegawai.csv', ';');

        $response = $this->actingAs($this->admin)->post(route('admin.karyawan.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('admin.karyawan.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('karyawans', [
            'nip' => '199403032017031003',
            'nama' => 'Bambang Sudiro',
            'sub_bagian' => 'Teknis',
        ]);
    }

    public function test_import_creates_new_jabatan_if_not_exists(): void
    {
        $this->assertDatabaseMissing('jabatans', [
            'nama_jabatan' => 'Analis Tata Laksana Pemilu',
        ]);

        $rows = [
            ['No', 'NIP', 'Nama Lengkap', 'Jabatan', 'Sub Bagian'],
            [1, '199504042018041004', 'Rian Hidayat', 'Analis Tata Laksana Pemilu', 'KUL'],
        ];

        $file = $this->createFakeExcelFile($rows);

        $this->actingAs($this->admin)->post(route('admin.karyawan.import'), [
            'file' => $file,
        ]);

        $this->assertDatabaseHas('jabatans', [
            'nama_jabatan' => 'Analis Tata Laksana Pemilu',
            'sub_bagian' => 'KUL',
        ]);

        $this->assertDatabaseHas('karyawans', [
            'nip' => '199504042018041004',
            'nama' => 'Rian Hidayat',
        ]);
    }

    public function test_import_skips_duplicate_nip_already_in_database(): void
    {
        $existingJabatan = Jabatan::factory()->create(['nama_jabatan' => 'Sekretaris']);
        Karyawan::create([
            'nip' => '19700101 200001 1 001',
            'nama' => 'Pegawai Lama',
            'jabatan_id' => $existingJabatan->id,
            'sub_bagian' => 'Sekretariat',
            'sort_order' => 1,
        ]);

        $rows = [
            ['No', 'NIP', 'Nama Lengkap', 'Jabatan', 'Sub Bagian'],
            [1, '197001012000011001', 'Pegawai Lama Coba Masuk Lagi', 'Sekretaris', 'Sekretariat'], // Sama tanpa spasi
            [2, '199605052019051005', 'Pegawai Baru Sah', 'Operator SIMAK', 'KUL'],
        ];

        $file = $this->createFakeExcelFile($rows);

        $response = $this->actingAs($this->admin)->post(route('admin.karyawan.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('admin.karyawan.index'));

        $importResult = session('import_result');
        $this->assertNotNull($importResult);
        $this->assertEquals(1, $importResult['imported_count']);
        $this->assertEquals(1, $importResult['skipped_count']);
        $this->assertCount(1, $importResult['skipped_details']);
        $this->assertStringContainsString('sudah terdaftar', $importResult['skipped_details'][0]['reason']);

        $this->assertDatabaseHas('karyawans', [
            'nip' => '199605052019051005',
            'nama' => 'Pegawai Baru Sah',
        ]);

        $this->assertDatabaseMissing('karyawans', [
            'nama' => 'Pegawai Lama Coba Masuk Lagi',
        ]);
    }

    public function test_import_skips_duplicate_nip_within_same_file(): void
    {
        $rows = [
            ['No', 'NIP', 'Nama Lengkap', 'Jabatan', 'Sub Bagian'],
            [1, '199706062020061006', 'Kembar Pertama', 'Kasubag Hukum', 'Hukum & SDM'],
            [2, '199706062020061006', 'Kembar Kedua', 'Kasubag Hukum', 'Hukum & SDM'],
        ];

        $file = $this->createFakeExcelFile($rows);

        $this->actingAs($this->admin)->post(route('admin.karyawan.import'), [
            'file' => $file,
        ]);

        $importResult = session('import_result');
        $this->assertEquals(1, $importResult['imported_count']);
        $this->assertEquals(1, $importResult['skipped_count']);
        $this->assertStringContainsString('duplikat di dalam file impor', $importResult['skipped_details'][0]['reason']);

        $this->assertDatabaseHas('karyawans', [
            'nama' => 'Kembar Pertama',
        ]);
        $this->assertDatabaseMissing('karyawans', [
            'nama' => 'Kembar Kedua',
        ]);
    }

    public function test_import_skips_invalid_and_incomplete_rows(): void
    {
        $rows = [
            ['No', 'NIP', 'Nama Lengkap', 'Jabatan', 'Sub Bagian'],
            [1, '', 'Tanpa NIP', 'Staf KUL', 'KUL'],
            [2, '199807072021071007', '', 'Staf Teknis', 'Teknis'],
            [3, '199808082021081008', 'Tanpa Jabatan', '', 'Rendatin'],
            [4, '199809092021091009', 'Lengkap dan Valid', 'Penyusun Program', 'Rendatin'],
        ];

        $file = $this->createFakeExcelFile($rows);

        $this->actingAs($this->admin)->post(route('admin.karyawan.import'), [
            'file' => $file,
        ]);

        $importResult = session('import_result');
        $this->assertEquals(1, $importResult['imported_count']);
        $this->assertEquals(3, $importResult['skipped_count']);

        $this->assertDatabaseHas('karyawans', [
            'nip' => '199809092021091009',
            'nama' => 'Lengkap dan Valid',
        ]);
    }

    public function test_import_handles_empty_file_gracefully(): void
    {
        $rows = [
            ['No', 'NIP', 'Nama Lengkap', 'Jabatan', 'Sub Bagian'],
        ];

        $file = $this->createFakeExcelFile($rows);

        $response = $this->actingAs($this->admin)->post(route('admin.karyawan.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('admin.karyawan.index'));
        $response->assertSessionHas('warning');
    }

    public function test_import_logs_activity_in_audit_trail(): void
    {
        $rows = [
            ['No', 'NIP', 'Nama Lengkap', 'Jabatan', 'Sub Bagian'],
            [1, '199910102022101010', 'Pegawai Audit Log', 'Operator Keuangan', 'KUL'],
        ];

        $file = $this->createFakeExcelFile($rows);

        $this->actingAs($this->admin)->post(route('admin.karyawan.import'), [
            'file' => $file,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'IMPORT',
            'module' => 'PEGAWAI',
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_import_api_json_response(): void
    {
        $rows = [
            ['No', 'NIP', 'Nama Lengkap', 'Jabatan', 'Sub Bagian'],
            [1, '200011112023111011', 'Pegawai JSON API', 'Pengadministrasi Umum', 'KUL'],
        ];

        $file = $this->createFakeExcelFile($rows);

        $response = $this->actingAs($this->admin)->postJson(route('admin.karyawan.import'), [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'total_rows',
            'imported_count',
            'skipped_count',
            'skipped_details',
            'created_jabatans_count',
        ]);
        $response->assertJson([
            'status' => 'success',
            'imported_count' => 1,
            'skipped_count' => 0,
        ]);
    }

    public function test_non_admin_cannot_import_karyawan(): void
    {
        $file = UploadedFile::fake()->create('test.xlsx', 10);

        $this->actingAs($this->supervisor)
            ->post(route('admin.karyawan.import'), ['file' => $file])
            ->assertStatus(403);

        $this->actingAs($this->karyawanUser)
            ->post(route('admin.karyawan.import'), ['file' => $file])
            ->assertStatus(403);

        auth()->logout();

        $this->post(route('admin.karyawan.import'), ['file' => $file])
            ->assertRedirect(route('login'));
    }

    public function test_karyawan_index_view_renders_template_and_import_buttons(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.karyawan.index'));

        $response->assertStatus(200);
        $response->assertSee('Unduh Template Excel');
        $response->assertSee('Impor Pegawai');
        $response->assertSee('modalImportKaryawan');
    }
}
