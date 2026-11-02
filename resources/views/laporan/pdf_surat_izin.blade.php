<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Izin Resmi - {{ $laporan->nama }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 20mm 20mm 20mm 20mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1a1a1a;
            font-size: 11pt;
            line-height: 1.45;
            margin: 0;
            padding: 0;
        }
        /* Kop Surat Resmi KPU */
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
        }
        .kop-table td {
            vertical-align: middle;
            padding: 0;
        }
        .kop-logo {
            width: 75px;
            text-align: left;
        }
        .kop-text {
            text-align: center;
            padding-right: 25px;
        }
        .kop-instansi {
            font-size: 14pt;
            font-weight: bold;
            color: #7a1c1c;
            letter-spacing: 0.5px;
            margin: 0;
            text-transform: uppercase;
        }
        .kop-sub {
            font-size: 11.5pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin: 2px 0;
            text-transform: uppercase;
        }
        .kop-alamat {
            font-size: 8.5pt;
            color: #333333;
            margin: 0;
            line-height: 1.3;
        }
        .kop-line-bold {
            border-top: 2.5px solid #000000;
            margin-top: 6px;
            margin-bottom: 1.5px;
        }
        .kop-line-thin {
            border-top: 0.8px solid #000000;
            margin-bottom: 16px;
        }

        /* Judul Naskah Dinas */
        .judul-surat {
            text-align: center;
            margin-top: 10px;
            margin-bottom: 18px;
        }
        .judul-text {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
            margin: 0;
        }
        .nomor-surat {
            font-size: 9.5pt;
            color: #333333;
            margin-top: 3px;
        }

        /* Isi & Tabel */
        .intro-text {
            text-align: justify;
            margin-bottom: 12px;
            font-size: 10.5pt;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            font-size: 10.5pt;
        }
        .data-table td {
            padding: 3.5px 0;
            vertical-align: top;
        }
        .data-table .label {
            width: 28%;
            color: #222222;
        }
        .data-table .separator {
            width: 3%;
            text-align: center;
        }
        .data-table .value {
            width: 69%;
            color: #000000;
            font-weight: 500;
        }

        /* Penutup */
        .closing-text {
            text-align: justify;
            margin-top: 14px;
            margin-bottom: 24px;
            font-size: 10.5pt;
        }

        /* Bagian Pengesahan / Tanda Tangan */
        .ttd-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .ttd-table td {
            vertical-align: top;
            padding: 0;
        }
        .qr-section {
            width: 48%;
            text-align: center;
            padding-right: 15px;
        }
        .qr-box {
            display: inline-block;
            border: 1px solid #d1d5db;
            padding: 6px;
            background: #ffffff;
            border-radius: 6px;
        }
        .qr-caption {
            font-size: 7.5pt;
            color: #555555;
            margin-top: 6px;
            line-height: 1.25;
        }
        .qr-uuid {
            font-family: monospace;
            font-size: 7pt;
            color: #7a1c1c;
            margin-top: 3px;
        }

        .pejabat-section {
            width: 52%;
            text-align: left;
            padding-left: 30px;
            font-size: 10.5pt;
        }
        .pejabat-nama {
            font-weight: bold;
            text-decoration: underline;
            margin-top: 55px;
            margin-bottom: 2px;
        }
        .pejabat-nip {
            font-size: 9.5pt;
            color: #333333;
        }
    </style>
</head>
<body>

    <!-- Kop Surat Resmi KPU -->
    <table class="kop-table">
        <tr>
            <td class="kop-logo">
                <img src="{{ $logoBase64 }}" width="68" alt="Logo KPU">
            </td>
            <td class="kop-text">
                <div class="kop-instansi">KOMISI PEMILIHAN UMUM</div>
                <div class="kop-sub">KOTA BUKITTINGGI</div>
                <div class="kop-alamat">
                    Jl. Pemuda No. 1, Benteng Pasar Atas, Kec. Guguk Panjang, Kota Bukittinggi<br>
                    Sumatera Barat 26113 &bull; Telp: (0752) 21102 &bull; Laman: kota-bukittinggi.kpu.go.id<br>
                    Pos-el: kpu.bukittinggikota@gmail.com
                </div>
            </td>
        </tr>
    </table>
    <div class="kop-line-bold"></div>
    <div class="kop-line-thin"></div>

    <!-- Judul Naskah Dinas -->
    <div class="judul-surat">
        <h4 class="judul-text">SURAT KETERANGAN PENGESAHAN KETIDAKHADIRAN KERJA</h4>
        <div class="nomor-surat">
            Nomor: 800/KPU-BKT/SLKP/{{ $laporan->tanggal ? $laporan->tanggal->format('Y') : date('Y') }}/{{ str_pad($laporan->id, 4, '0', STR_PAD_LEFT) }}
        </div>
    </div>

    <!-- Paragraf Pembuka -->
    <div class="intro-text">
        Sekretariat Komisi Pemilihan Umum Kota Bukittinggi dengan ini menerangkan dan mengesahkan bahwa Pegawai Negeri Sipil / Pegawai Pemerintah yang tersebut di bawah ini:
    </div>

    <!-- Identitas Pegawai -->
    <table class="data-table">
        <tr>
            <td class="label">Nama Lengkap</td>
            <td class="separator">:</td>
            <td class="value"><strong>{{ $laporan->nama }}</strong></td>
        </tr>
        <tr>
            <td class="label">NIP</td>
            <td class="separator">:</td>
            <td class="value">{{ $laporan->nip ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Jabatan</td>
            <td class="separator">:</td>
            <td class="value">{{ $laporan->jabatan ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Unit Kerja / Instansi</td>
            <td class="separator">:</td>
            <td class="value">Sekretariat KPU Kota Bukittinggi</td>
        </tr>
    </table>

    <div class="intro-text">
        Diberikan persetujuan resmi atas permohonan ketidakhadiran kerja dengan rincian sebagai berikut:
    </div>

    <!-- Rincian Izin / Ketidakhadiran -->
    <table class="data-table">
        <tr>
            <td class="label">Jenis Ketidakhadiran</td>
            <td class="separator">:</td>
            <td class="value"><strong>{{ $laporan->keterangan ?? 'Izin Kerja' }}</strong></td>
        </tr>
        <tr>
            <td class="label">Periode Waktu</td>
            <td class="separator">:</td>
            <td class="value">
                {{ $laporan->tanggal?->translatedFormat('d F Y') }}
                @if ($laporan->tanggal_selesai && $laporan->tanggal_selesai != $laporan->tanggal)
                    s/d {{ $laporan->tanggal_selesai->translatedFormat('d F Y') }}
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Jangka Waktu</td>
            <td class="separator">:</td>
            <td class="value">{{ $laporan->lama_hari ?? '1 Hari' }}</td>
        </tr>
        <tr>
            <td class="label">Alasan / Keperluan</td>
            <td class="separator">:</td>
            <td class="value">{{ $laporan->alasan ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Status Pengesahan</td>
            <td class="separator">:</td>
            <td class="value" style="color: #047857;">
                <strong>DISETUJUI &amp; DISAHKAN</strong>
            </td>
        </tr>
    </table>

    <!-- Paragraf Penutup -->
    <div class="closing-text">
        Demikian Surat Keterangan Pengesahan Ketidakhadiran Kerja ini diterbitkan secara elektronik melalui Sistem Laporan Kinerja Pegawai (SLKP) KPU Kota Bukittinggi untuk dapat dipergunakan sebagaimana mestinya.
    </div>

    <!-- Tanda Tangan & QR Code Verifikasi -->
    <table class="ttd-table">
        <tr>
            <!-- Kolom QR Code Digital Validation -->
            <td class="qr-section">
                <div class="qr-box">
                    <img src="{{ $qrCodeDataUri }}" width="96" height="96" alt="QR Code Verifikasi">
                </div>
                <div class="qr-caption">
                    <strong>Pindai QR Code untuk Validasi</strong><br>
                    Dokumen digital resmi tervalidasi online pada pangkalan data SLKP KPU Kota Bukittinggi.
                </div>
                <div class="qr-uuid">
                    UUID: {{ $laporan->uuid }}
                </div>
            </td>

            <!-- Kolom Pejabat Penilai / Approver -->
            <td class="pejabat-section">
                <div>Bukittinggi, {{ $laporan->approved_at ? $laporan->approved_at->translatedFormat('d F Y') : ($laporan->tanggal ? $laporan->tanggal->translatedFormat('d F Y') : date('d F Y')) }}</div>
                <div style="margin-top: 2px;">Pejabat yang Menyetujui,</div>
                <div style="font-size: 8.5pt; color: #555555; margin-top: 4px;">
                    <em>(Ditandatangani secara elektronik)</em>
                </div>

                <div class="pejabat-nama">
                    {{ $laporan->approver?->name ?? 'Kasubag / Pejabat Penilai' }}
                </div>
                <div class="pejabat-nip">
                    NIP. {{ $laporan->approver?->nip ?? '-' }}
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
