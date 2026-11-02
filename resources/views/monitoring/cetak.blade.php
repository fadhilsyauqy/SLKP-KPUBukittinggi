<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Rekapitulasi Presensi & Kinerja Pegawai</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h3, .header h4 { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 6px 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="margin-bottom: 15px;">
        <button onclick="window.print()">Cetak Dokumen</button>
        <button onclick="window.close()">Tutup</button>
    </div>

    <div class="header">
        <h3>KOMISI PEMILIHAN UMUM KOTA BUKITTINGGI</h3>
        <h4>REKAPITULASI LAPORAN KINERJA &amp; PRESENSI HARIAN PEGAWAI</h4>
        <small>Dicetak pada: {{ date('d-m-Y H:i:s') }}</small>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th>NIP</th>
                <th>Nama Pegawai</th>
                <th>Jabatan</th>
                <th>Tanggal</th>
                <th>Tipe / Keterangan</th>
                <th>Uraian Kegiatan / Alasan</th>
                <th>Output / Hasil</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($laporans as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $row->nip }}</td>
                    <td>{{ $row->nama }}</td>
                    <td>{{ $row->jabatan }}</td>
                    <td>{{ $row->tanggal?->format('d-m-Y') }}</td>
                    <td>
                        {{ $row->tipe == 'hadir' ? 'Hadir ('.$row->jam_masuk.'-'.$row->jam_keluar.')' : $row->keterangan.' ('.$row->lama_hari.')' }}
                    </td>
                    <td>{{ $row->tipe == 'hadir' ? $row->uraian : $row->alasan }}</td>
                    <td>{{ $row->tipe == 'hadir' ? $row->output : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center;">Tidak ada data yang dicetak.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
