<?php

namespace App\Services;

use App\Models\Jabatan;
use App\Models\Karyawan;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Shuchkin\SimpleXLSX;
use Shuchkin\SimpleXLSXGen;

class KaryawanImportService
{
    /**
     * Hasilkan dokumen format contoh impor data pegawai (.xlsx).
     */
    public function generateTemplate(): SimpleXLSXGen
    {
        $headerStyle = '<style bgcolor="#8B1518" color="#FFFFFF"><center><b>';
        $headerStyleClose = '</b></center></style>';

        $header = [
            $headerStyle.'No'.$headerStyleClose,
            $headerStyle.'NIP'.$headerStyleClose,
            $headerStyle.'Nama Lengkap'.$headerStyleClose,
            $headerStyle.'Jabatan'.$headerStyleClose,
            $headerStyle.'Sub Bagian'.$headerStyleClose,
        ];

        $sampleData = [
            [1, '198503152010011002', 'Budi Santoso, S.Kom', 'Pengolah Data dan Informasi', 'Rendatin'],
            [2, '199008202015022001', 'Siti Rahma, S.H.', 'Penelaah Teknis Kebijakan', 'Hukum & SDM'],
            [3, '199512102020121003', 'Ahmad Fauzi', 'Staf Pelaksana Keuangan dan Logistik', 'KUL'],
            [4, '198804152012011004', 'Dewi Lestari, S.IP', 'Kasubag Teknis Penyelenggaraan Pemilu', 'Teknis'],
            [5, '197901012005011005', 'Drs. Hendra Wijaya', 'Sekretaris', 'Sekretariat'],
        ];

        $rows = array_merge([$header], $sampleData);

        return SimpleXLSXGen::fromArray($rows);
    }

    /**
     * Baca file Excel / CSV yang diunggah, validasi kelayakan data,
     * dan simpan ke database secara transaksional.
     *
     * @return array<string, mixed>
     */
    public function parseAndImport(UploadedFile|string $file): array
    {
        $filePath = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        $extension = strtolower(
            $file instanceof UploadedFile
                ? ($file->getClientOriginalExtension() ?: $file->extension() ?: '')
                : pathinfo($filePath, PATHINFO_EXTENSION)
        );

        $rows = $this->extractRowsFromFile($filePath, $extension);

        if (empty($rows)) {
            return [
                'status' => 'error',
                'message' => 'File tidak berisi data atau format file tidak dapat dibaca.',
                'total_rows' => 0,
                'imported_count' => 0,
                'skipped_count' => 0,
                'skipped_details' => [],
                'created_jabatans_count' => 0,
                'created_jabatans' => [],
            ];
        }

        // Deteksi header kolom
        $columnMapping = $this->detectColumns($rows);
        $headerRowIndex = $columnMapping['header_index'];
        $colNip = $columnMapping['nip'];
        $colNama = $columnMapping['nama'];
        $colJabatan = $columnMapping['jabatan'];
        $colSubBagian = $columnMapping['sub_bagian'];

        $startRow = $headerRowIndex !== null ? $headerRowIndex + 1 : 0;
        $totalRowsInFile = count($rows);

        if ($startRow >= $totalRowsInFile) {
            return [
                'status' => 'warning',
                'message' => 'File hanya memiliki baris judul tanpa data pegawai untuk diimpor.',
                'total_rows' => 0,
                'imported_count' => 0,
                'skipped_count' => 0,
                'skipped_details' => [],
                'created_jabatans_count' => 0,
                'created_jabatans' => [],
            ];
        }

        // Cache NIP dan Jabatan yang telah ada untuk performa optimal O(1)
        $existingKaryawans = Karyawan::pluck('nip')->all();
        $existingNipMap = [];
        foreach ($existingKaryawans as $existingNip) {
            $nipStr = trim((string) $existingNip);
            $cleanNip = preg_replace('/\s+/', '', $nipStr);
            $existingNipMap[$cleanNip] = true;
            $existingNipMap[$nipStr] = true;
        }

        $existingJabatans = Jabatan::all();
        $jabatanMap = [];
        foreach ($existingJabatans as $j) {
            $jabatanMap[mb_strtolower(trim($j->nama_jabatan))] = $j;
        }

        $importedCount = 0;
        $skippedCount = 0;
        $skippedDetails = [];
        $createdJabatans = [];
        $seenNipsInBatch = [];
        $validProcessedRows = 0;

        DB::beginTransaction();

        try {
            for ($i = $startRow; $i < $totalRowsInFile; $i++) {
                $row = $rows[$i];
                $rowNum = $i + 1; // 1-based index untuk feedback yang mudah dipahami admin

                $rawNip = $colNip !== null && isset($row[$colNip]) ? trim((string) $row[$colNip]) : '';
                $rawNama = $colNama !== null && isset($row[$colNama]) ? trim((string) $row[$colNama]) : '';
                $rawJabatan = $colJabatan !== null && isset($row[$colJabatan]) ? trim((string) $row[$colJabatan]) : '';
                $rawSubBagian = $colSubBagian !== null && isset($row[$colSubBagian]) ? trim((string) $row[$colSubBagian]) : '';

                // Abaikan baris yang seluruhnya kosong
                if ($rawNip === '' && $rawNama === '' && $rawJabatan === '') {
                    continue;
                }

                $validProcessedRows++;

                // Validasi field wajib
                if ($rawNip === '') {
                    $skippedCount++;
                    $skippedDetails[] = [
                        'row' => $rowNum,
                        'nip' => '-',
                        'nama' => $rawNama ?: '-',
                        'reason' => 'NIP pegawai wajib diisi.',
                    ];

                    continue;
                }

                if ($rawNama === '') {
                    $skippedCount++;
                    $skippedDetails[] = [
                        'row' => $rowNum,
                        'nip' => $rawNip,
                        'nama' => '-',
                        'reason' => 'Nama lengkap pegawai wajib diisi.',
                    ];

                    continue;
                }

                if ($rawJabatan === '') {
                    $skippedCount++;
                    $skippedDetails[] = [
                        'row' => $rowNum,
                        'nip' => $rawNip,
                        'nama' => $rawNama,
                        'reason' => 'Nama jabatan pegawai wajib diisi.',
                    ];

                    continue;
                }

                // Normalisasi dan validasi keunikan NIP
                $cleanNip = preg_replace('/\s+/', '', $rawNip);

                if (isset($seenNipsInBatch[$cleanNip])) {
                    $skippedCount++;
                    $skippedDetails[] = [
                        'row' => $rowNum,
                        'nip' => $rawNip,
                        'nama' => $rawNama,
                        'reason' => "NIP {$rawNip} duplikat di dalam file impor.",
                    ];

                    continue;
                }

                if (isset($existingNipMap[$cleanNip]) || isset($existingNipMap[$rawNip])) {
                    $skippedCount++;
                    $skippedDetails[] = [
                        'row' => $rowNum,
                        'nip' => $rawNip,
                        'nama' => $rawNama,
                        'reason' => "NIP {$rawNip} sudah terdaftar di sistem.",
                    ];

                    continue;
                }

                // Normalisasi Sub Bagian
                $subBagian = $this->normalizeSubBagian($rawSubBagian);

                // Temukan atau buat Jabatan otomatis
                $jabatanKey = mb_strtolower($rawJabatan);
                if (isset($jabatanMap[$jabatanKey])) {
                    $jabatan = $jabatanMap[$jabatanKey];
                    if (empty($jabatan->sub_bagian) && ! empty($subBagian)) {
                        $jabatan->update(['sub_bagian' => $subBagian]);
                    }
                } else {
                    $jabatan = Jabatan::create([
                        'nama_jabatan' => $rawJabatan,
                        'sub_bagian' => $subBagian,
                    ]);
                    $jabatanMap[$jabatanKey] = $jabatan;
                    $createdJabatans[] = $jabatan->nama_jabatan;
                }

                // Tentukan sort_order hierarki dan buat Karyawan
                $sortOrder = Karyawan::determineSortOrder($jabatan->nama_jabatan);
                $effectiveSubBagian = $subBagian ?: $jabatan->sub_bagian;

                Karyawan::create([
                    'nip' => $rawNip,
                    'nama' => $rawNama,
                    'jabatan_id' => $jabatan->id,
                    'sub_bagian' => $effectiveSubBagian,
                    'sort_order' => $sortOrder,
                ]);

                $seenNipsInBatch[$cleanNip] = true;
                $existingNipMap[$cleanNip] = true;
                $existingNipMap[$rawNip] = true;
                $importedCount++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return [
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat memproses data impor: '.$e->getMessage(),
                'total_rows' => $validProcessedRows,
                'imported_count' => 0,
                'skipped_count' => $skippedCount,
                'skipped_details' => $skippedDetails,
                'created_jabatans_count' => 0,
                'created_jabatans' => [],
            ];
        }

        if ($validProcessedRows === 0) {
            return [
                'status' => 'warning',
                'message' => 'File tidak berisi baris data pegawai untuk diimpor.',
                'total_rows' => 0,
                'imported_count' => 0,
                'skipped_count' => 0,
                'skipped_details' => [],
                'created_jabatans_count' => 0,
                'created_jabatans' => [],
            ];
        }

        if ($importedCount > 0) {
            ActivityLogger::log(
                'IMPORT',
                'PEGAWAI',
                "Berhasil mengimpor massal {$importedCount} data pegawai dari file.",
                [
                    'total_rows' => $validProcessedRows,
                    'imported_count' => $importedCount,
                    'skipped_count' => $skippedCount,
                    'created_jabatans_count' => count($createdJabatans),
                ]
            );
        }

        $message = "Berhasil mengimpor {$importedCount} data pegawai baru.";
        if (count($createdJabatans) > 0) {
            $message .= ' ('.count($createdJabatans).' jabatan baru otomatis ditambahkan).';
        }
        if ($skippedCount > 0) {
            $message .= " Sebanyak {$skippedCount} baris dilewati karena duplikat atau tidak lengkap.";
        }

        return [
            'status' => 'success',
            'message' => $message,
            'total_rows' => $validProcessedRows,
            'imported_count' => $importedCount,
            'skipped_count' => $skippedCount,
            'skipped_details' => $skippedDetails,
            'created_jabatans_count' => count($createdJabatans),
            'created_jabatans' => $createdJabatans,
        ];
    }

    /**
     * Ekstrak baris data dari file Excel atau CSV.
     *
     * @return array<int, array<int, mixed>>
     */
    protected function extractRowsFromFile(string $filePath, string $extension): array
    {
        if (in_array($extension, ['csv', 'txt'])) {
            return $this->readCsv($filePath);
        }

        $xlsx = SimpleXLSX::parse($filePath);
        if ($xlsx) {
            return $xlsx->rows();
        }

        // Fallback coba baca sebagai CSV jika SimpleXLSX gagal
        return $this->readCsv($filePath);
    }

    /**
     * Membaca file CSV dengan deteksi otomatis delimiter (, atau ;) dan penanganan UTF-8 BOM.
     *
     * @return array<int, array<int, mixed>>
     */
    protected function readCsv(string $filePath): array
    {
        $rows = [];
        if (! file_exists($filePath) || ! is_readable($filePath)) {
            return $rows;
        }

        $handle = fopen($filePath, 'r');
        if (! $handle) {
            return $rows;
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);

            return $rows;
        }

        // Bersihkan UTF-8 BOM jika ada
        if (str_starts_with($firstLine, "\xEF\xBB\xBF")) {
            $firstLine = substr($firstLine, 3);
        }

        // Deteksi delimiter
        $semicolons = substr_count($firstLine, ';');
        $commas = substr_count($firstLine, ',');
        $tabs = substr_count($firstLine, "\t");

        $delimiter = ',';
        if ($semicolons > $commas && $semicolons > $tabs) {
            $delimiter = ';';
        } elseif ($tabs > $commas && $tabs > $semicolons) {
            $delimiter = "\t";
        }

        $parsedFirstLine = str_getcsv($firstLine, $delimiter);
        if (! empty($parsedFirstLine)) {
            $rows[] = $parsedFirstLine;
        }

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = $data;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Deteksi posisi kolom NIP, Nama, Jabatan, dan Sub Bagian dari baris header.
     *
     * @param  array<int, array<int, mixed>>  $rows
     * @return array<string, int|null>
     */
    protected function detectColumns(array $rows): array
    {
        $headerRowIndex = null;
        $colNip = null;
        $colNama = null;
        $colJabatan = null;
        $colSubBagian = null;

        $maxInspect = min(5, count($rows));

        for ($i = 0; $i < $maxInspect; $i++) {
            $row = $rows[$i];
            foreach ($row as $colIdx => $cell) {
                $val = strtolower(trim((string) $cell));

                if (preg_match('/\bnip\b|nomor\s*induk/i', $val)) {
                    $colNip = $colIdx;
                    $headerRowIndex = $i;
                } elseif (preg_match('/\bnama\b/i', $val)) {
                    $colNama = $colIdx;
                    $headerRowIndex = $i;
                } elseif (preg_match('/\bjabatan\b/i', $val)) {
                    $colJabatan = $colIdx;
                } elseif (preg_match('/sub\s*bagian|divisi/i', $val)) {
                    $colSubBagian = $colIdx;
                }
            }

            if ($colNip !== null && $colNama !== null) {
                break;
            }
        }

        // Fallback jika tidak ditemukan nama header yang cocok
        if ($headerRowIndex === null) {
            $firstRowCount = count($rows[0] ?? []);
            if ($firstRowCount >= 5) {
                // Pola umum template: [0: No, 1: NIP, 2: Nama, 3: Jabatan, 4: Sub Bagian]
                $colNip = 1;
                $colNama = 2;
                $colJabatan = 3;
                $colSubBagian = 4;
            } elseif ($firstRowCount >= 4) {
                // Pola 4 kolom: [0: NIP, 1: Nama, 2: Jabatan, 3: Sub Bagian]
                $colNip = 0;
                $colNama = 1;
                $colJabatan = 2;
                $colSubBagian = 3;
            } else {
                $colNip = 0;
                $colNama = 1;
                $colJabatan = 2;
                $colSubBagian = 3;
            }
        }

        return [
            'header_index' => $headerRowIndex,
            'nip' => $colNip,
            'nama' => $colNama,
            'jabatan' => $colJabatan,
            'sub_bagian' => $colSubBagian,
        ];
    }

    /**
     * Normalisasi teks Sub Bagian sesuai konstanta resmi KPU Bukittinggi.
     */
    public function normalizeSubBagian(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        $trimmed = trim($input);
        if ($trimmed === '' || $trimmed === '-') {
            return null;
        }

        $subBagianList = Karyawan::getSubBagianList();

        // Kecocokan persis pada key
        foreach ($subBagianList as $key => $label) {
            if (strcasecmp($trimmed, $key) === 0) {
                return $key;
            }
        }

        // Kecocokan persis pada label lengkap
        foreach ($subBagianList as $key => $label) {
            if (strcasecmp($trimmed, $label) === 0) {
                return $key;
            }
        }

        // Heuristik kata kunci
        $lower = mb_strtolower($trimmed);
        if (str_contains($lower, 'teknis') || str_contains($lower, 'hupmas')) {
            return 'Teknis';
        }
        if (str_contains($lower, 'hukum') || str_contains($lower, 'sdm')) {
            return 'Hukum & SDM';
        }
        if (str_contains($lower, 'kul') || str_contains($lower, 'keuangan') || str_contains($lower, 'logistik')) {
            return 'KUL';
        }
        if (str_contains($lower, 'rendatin') || str_contains($lower, 'perencanaan') || str_contains($lower, 'data')) {
            return 'Rendatin';
        }
        if (str_contains($lower, 'sekretariat') || str_contains($lower, 'pimpinan')) {
            return 'Sekretariat';
        }

        return $trimmed;
    }
}
