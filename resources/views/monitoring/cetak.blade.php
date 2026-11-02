<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Rekapitulasi Presensi & Kinerja Pegawai - KPU Kota Bukittinggi</title>
    <link rel="icon" href="{{ asset('assets/img/logo-kpu.png') }}" type="image/png">
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
            margin: 20px;
        }
        .header {
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 3px double #000;
            padding-bottom: 12px;
            margin-bottom: 16px;
            text-align: center;
            position: relative;
        }
        .header img {
            position: absolute;
            left: 10px;
            top: 5px;
            width: 65px;
            height: auto;
        }
        .header-text h3 {
            margin: 0 0 4px 0;
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .header-text h4 {
            margin: 0 0 4px 0;
            font-size: 13px;
            font-weight: bold;
        }
        .header-text p {
            margin: 0;
            font-size: 10px;
            color: #333;
        }
        .meta-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        th, td {
            border: 1px solid #333;
            padding: 5px 6px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
        }
        .text-center { text-align: center; }
        .signature-section {
            width: 100%;
            margin-top: 30px;
            display: flex;
            justify-content: flex-end;
        }
        .signature-box {
            width: 250px;
            text-align: center;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                margin: 0;
            }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="margin-bottom: 15px; padding: 10px; background-color: #f8f9fa; border: 1px solid #ddd; border-radius: 4px;">
        <button onclick="window.print()" style="padding: 6px 14px; font-weight: bold; cursor: pointer;">Cetak Dokumen (Print)</button>
        <button onclick="window.close()" style="padding: 6px 14px; cursor: pointer; margin-left: 8px;">Tutup Halaman</button>
        <span style="margin-left: 15px; font-size: 11px; color: #666;">Gunakan opsi kertas A4 Landscape pada pengaturan printer untuk hasil optimal.</span>
    </div>

    <div class="header">
        <img src="{{ asset('assets/img/logo-kpu.png') }}" alt="Logo KPU">
        <div class="header-text">
            <h3>KOMISI PEMILIHAN UMUM KOTA BUKITTINGGI</h3>
            <h4>REKAPITULASI LAPORAN KINERJA &amp; PRESENSI HARIAN PEGAWAI</h4>
            <p>Jl. Cindua Mato No.7, Benteng Ps. Atas, Kec. Guguk Panjang, Kota Bukittinggi, Sumatera Barat 26136</p>
        </div>
    </div>

    <div class="meta-info">
        <div><strong>Total Data Tercetak:</strong> {{ $laporans->count() }} Laporan</div>
        <div><strong>Waktu Cetak:</strong> {{ date('d-m-Y H:i:s') }} WIB</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 25px;">No</th>
                <th style="width: 110px;">NIP</th>
                <th style="width: 130px;">Nama Pegawai</th>
                <th style="width: 120px;">Jabatan</th>
                <th style="width: 70px;">Tanggal</th>
                <th style="width: 90px;">Tipe / Jam</th>
                <th>Uraian Kegiatan / Alasan</th>
                <th style="width: 110px;">Output / Hasil</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($laporans as $index => $row)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $row->nip }}</td>
                    <td><strong>{{ $row->nama }}</strong></td>
                    <td>{{ $row->jabatan }}</td>
                    <td class="text-center">{{ $row->tanggal?->format('d-m-Y') }}</td>
                    <td>
                        @if ($row->tipe == 'hadir')
                            Hadir ({{ $row->jam_masuk }} - {{ $row->jam_keluar }})
                        @else
                            {{ $row->keterangan }} ({{ $row->lama_hari }})
                        @endif
                    </td>
                    <td>
                        {{ $row->tipe == 'hadir' ? $row->uraian : $row->alasan }}
                    </td>
                    <td>
                        {{ $row->tipe == 'hadir' ? $row->output : '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">Tidak ada data laporan yang dicetak.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="signature-section">
        <div class="signature-box">
            <p style="margin-bottom: 60px;">
                Bukittinggi, {{ date('d F Y') }}<br>
                <strong>Sekretaris KPU Kota Bukittinggi</strong>
            </p>
            <p style="text-decoration: underline; font-weight: bold; margin-bottom: 2px;">
                Nama Pejabat
            </p>
            <p style="margin: 0;">NIP. 197001012000011001</p>
        </div>
    </div>
</body>
</html>
