@extends('layouts.guest')

@section('title', 'Verifikasi Dokumen Digital - KPU Kota Bukittinggi')

@push('styles')
<style>
    body {
        background: #f1f5f9;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem 1rem;
    }
    .verify-card {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        border: 1px solid #e2e8f0;
        max-width: 650px;
        width: 100%;
        overflow: hidden;
    }
    .verify-header {
        background: linear-gradient(135deg, #7a1c1c 0%, #521010 100%);
        color: #ffffff;
        padding: 1.75rem 2rem;
        text-align: center;
    }
    .status-badge-lg {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.65rem 1.25rem;
        border-radius: 50px;
        font-weight: 700;
        font-size: 0.95rem;
        letter-spacing: 0.5px;
    }
</style>
@endpush

@section('content')
<div class="verify-card">
    <!-- Header Resmi KPU -->
    <div class="verify-header">
        <img src="{{ asset('assets/img/logo-kpu.png') }}" alt="Logo KPU" height="54" class="mb-2">
        <h6 class="fw-bold mb-0 text-uppercase" style="letter-spacing: 0.5px; font-size: 0.95rem;">
            Komisi Pemilihan Umum Kota Bukittinggi
        </h6>
        <small class="text-white-50" style="font-size: 0.75rem;">
            Sistem Laporan Kinerja Pegawai (SLKP) &bull; Portal Verifikasi Dokumen
        </small>
    </div>

    <div class="p-4 p-md-5">
        @if ($status === 'valid')
            <!-- Status Sah & Terverifikasi -->
            <div class="text-center mb-4">
                <div class="status-badge-lg bg-success text-white shadow-sm mb-2">
                    <i class="bi bi-shield-fill-check fs-5"></i> DOKUMEN RESMI TERVERIFIKASI
                </div>
                <p class="text-muted small mb-0 mt-2">
                    Dokumen surat keterangan ini dinyatakan <strong>SAH &amp; VALID</strong>, diterbitkan secara elektronik oleh Sekretariat KPU Kota Bukittinggi.
                </p>
            </div>

            <!-- Rincian Dokumen -->
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-sm align-middle mb-0" style="font-size: 0.85rem;">
                    <tbody>
                        <tr>
                            <th class="bg-light text-muted" style="width: 38%;">Nomor Naskah / UUID</th>
                            <td class="font-monospace fw-semibold text-break">{{ $laporan->uuid }}</td>
                        </tr>
                        <tr>
                            <th class="bg-light text-muted">Nama Pegawai</th>
                            <td class="fw-bold text-dark">{{ $laporan->nama }}</td>
                        </tr>
                        <tr>
                            <th class="bg-light text-muted">NIP</th>
                            <td class="font-monospace">{{ $laporan->nip ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th class="bg-light text-muted">Jabatan Pegawai</th>
                            <td>{{ $laporan->jabatan ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th class="bg-light text-muted">Jenis Ketidakhadiran</th>
                            <td>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1">
                                    {{ $laporan->keterangan ?? 'Izin Kerja' }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th class="bg-light text-muted">Periode Waktu</th>
                            <td class="fw-semibold">
                                {{ $laporan->tanggal?->translatedFormat('d F Y') }}
                                @if ($laporan->tanggal_selesai && $laporan->tanggal_selesai != $laporan->tanggal)
                                    s/d {{ $laporan->tanggal_selesai->translatedFormat('d F Y') }}
                                @endif
                                <span class="text-muted fw-normal ms-1">({{ $laporan->lama_hari ?? '1 Hari' }})</span>
                            </td>
                        </tr>
                        <tr>
                            <th class="bg-light text-muted">Alasan / Keperluan</th>
                            <td>{{ $laporan->alasan ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th class="bg-light text-muted">Pejabat yang Menyetujui</th>
                            <td class="fw-semibold text-success">
                                <i class="bi bi-person-check-fill me-1"></i> {{ $laporan->approver?->name ?? 'Kasubag / Pimpinan KPU' }}
                            </td>
                        </tr>
                        <tr>
                            <th class="bg-light text-muted">Waktu Verifikasi Persetujuan</th>
                            <td class="small text-muted">
                                {{ $laporan->approved_at ? $laporan->approved_at->translatedFormat('d F Y H:i').' WIB' : '-' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="alert alert-light border small text-muted mb-4 d-flex align-items-center gap-2">
                <i class="bi bi-patch-check-fill text-success fs-4 flex-shrink-0"></i>
                <div>
                    Tanda tangan digital ini menggunakan token kriptografi unik yang dicocokkan secara realtime dengan server pangkalan data SLKP KPU Kota Bukittinggi.
                </div>
            </div>

        @elseif ($status === 'pending')
            <!-- Status Menunggu Persetujuan -->
            <div class="text-center mb-4">
                <div class="status-badge-lg bg-warning text-dark shadow-sm mb-2">
                    <i class="bi bi-hourglass-split fs-5"></i> DALAM PROSES VERIFIKASI
                </div>
                <p class="text-muted small mb-0 mt-2">
                    Dokumen ini telah diajukan namun <strong>belum mendapatkan pengesahan akhir</strong> dari atasan berwenang.
                </p>
            </div>

            <div class="p-3 bg-light rounded text-center small text-muted mb-4">
                Pengajuan a.n. <strong>{{ $laporan->nama }}</strong> ({{ $laporan->keterangan ?? 'Izin' }}) saat ini masih dalam antrean verifikasi pimpinan.
            </div>

        @elseif ($status === 'rejected')
            <!-- Status Ditolak -->
            <div class="text-center mb-4">
                <div class="status-badge-lg bg-danger text-white shadow-sm mb-2">
                    <i class="bi bi-x-circle-fill fs-5"></i> DOKUMEN DITOLAK / TIDAK BERLAKU
                </div>
                <p class="text-muted small mb-0 mt-2">
                    Pengajuan izin ini telah <strong>ditolak</strong> oleh pejabat penilai sehingga tidak memiliki kekuatan pengesahan kedinasan.
                </p>
            </div>

            @if($laporan->catatan_approval)
                <div class="alert alert-danger small mb-4">
                    <strong>Catatan Penolakan:</strong> {{ $laporan->catatan_approval }}
                </div>
            @endif

        @else
            <!-- Status Tidak Ditemukan -->
            <div class="text-center mb-4">
                <div class="status-badge-lg bg-secondary text-white shadow-sm mb-2">
                    <i class="bi bi-exclamation-triangle-fill fs-5"></i> DOKUMEN TIDAK DITEMUKAN
                </div>
                <h6 class="fw-bold text-danger mt-3">Tanda Tangan Digital Tidak Terdaftar</h6>
                <p class="text-muted small mb-0 mt-1">
                    Kode registrasi dokumen ini tidak valid atau tidak tercatat dalam basis data resmi Sekretariat KPU Kota Bukittinggi. Waspadai upaya pemalsuan dokumen negara.
                </p>
            </div>
        @endif

        <div class="text-center pt-2">
            <a href="{{ url('/') }}" class="btn btn-outline-secondary btn-sm px-4">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Beranda
            </a>
        </div>
    </div>
</div>
@endsection
