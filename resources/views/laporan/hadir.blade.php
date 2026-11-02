@extends('layouts.app')

@section('title', 'Form Laporan Hadir - SLKP KPU Bukittinggi')
@section('page-title', 'Formulir Laporan Hadir')

@section('content')
<div class="container-fluid px-0 px-md-2">
    <!-- Page Header & Tab Switcher (Full Width & Clean) -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h5 class="fw-bold text-dark mb-0.5">FORM LAPORAN KINERJA PEGAWAI HARIAN</h5>
            <p class="text-muted small mb-0">Laporkan kehadiran dan uraian hasil pekerjaan Anda setiap hari kerja</p>
        </div>
        <div class="tab-switcher">
            <a href="{{ route('laporan.hadir') }}" class="tab-item active">
                <i class="bi bi-file-earmark-check"></i> Hadir
            </a>
            <a href="{{ route('laporan.tidakhadir') }}" class="tab-item">
                <i class="bi bi-calendar-x"></i> Izin / Cuti / Sakit
            </a>
        </div>
    </div>

    <!-- Full-Width Horizontal Form Card (Single Screen Viewport) -->
    <div class="card border-0 shadow-sm w-100">
        <div class="card-body p-3 p-lg-4">
            <form id="laporanHadirForm" action="{{ route('laporan.hadir.store') }}" method="POST">
                @csrf

                <div class="row g-4">
                    <!-- KOLOM KIRI: Identitas Pegawai & Waktu Presensi -->
                    <div class="col-lg-5 border-end-lg pe-lg-4">
                        <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                            <div class="rounded-circle bg-danger bg-opacity-10 text-danger p-2 me-2.5 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                                <i class="bi bi-person-badge fs-6"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-0 small text-uppercase">1. Identitas &amp; Waktu Presensi</h6>
                        </div>

                        <!-- Nama Pegawai -->
                        <div class="mb-3">
                            <label for="nama" class="form-label">Nama Lengkap Pegawai <span class="text-danger">*</span></label>
                            <select name="nama" id="nama" class="form-select @error('nama') is-invalid @enderror" required>
                                <option value="">-- Pilih Nama Lengkap Anda --</option>
                                @foreach ($karyawans as $karyawan)
                                    <option value="{{ $karyawan->nama }}"
                                            data-nip="{{ $karyawan->nip }}"
                                            data-jabatan="{{ $karyawan->jabatan?->nama_jabatan }}"
                                            {{ (old('nama') == $karyawan->nama || ($currentKaryawan && $currentKaryawan->nama == $karyawan->nama)) ? 'selected' : '' }}>
                                        {{ $karyawan->nama }} ({{ $karyawan->jabatan?->nama_jabatan ?? 'Staf' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('nama')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- NIP & Jabatan (2 Kolom Berdampingan) -->
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="nip" class="form-label">NIP Pegawai <span class="text-danger">*</span></label>
                                <input type="text"
                                       name="nip"
                                       id="nip"
                                       class="form-control bg-light @error('nip') is-invalid @enderror"
                                       value="{{ old('nip', $currentKaryawan?->nip) }}"
                                       readonly
                                       required
                                       placeholder="Otomatis">
                                @error('nip')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-6">
                                <label for="jabatan" class="form-label">Jabatan</label>
                                <input type="text"
                                       name="jabatan"
                                       id="jabatan"
                                       class="form-control bg-light @error('jabatan') is-invalid @enderror"
                                       value="{{ old('jabatan', $currentKaryawan?->jabatan?->nama_jabatan) }}"
                                       readonly
                                       placeholder="Otomatis">
                                @error('jabatan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Tanggal & Jam (3 Kolom Berdampingan) -->
                        <div class="row g-2">
                            <div class="col-12 col-sm-4">
                                <label for="tanggal" class="form-label">Tanggal Kerja <span class="text-danger">*</span></label>
                                <input type="date"
                                       name="tanggal"
                                       id="tanggal"
                                       class="form-control @error('tanggal') is-invalid @enderror"
                                       value="{{ old('tanggal', date('Y-m-d')) }}"
                                       required>
                                @error('tanggal')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-6 col-sm-4">
                                <label for="jam_masuk" class="form-label">Jam Masuk <span class="text-danger">*</span></label>
                                <input type="time"
                                       name="jam_masuk"
                                       id="jam_masuk"
                                       class="form-control @error('jam_masuk') is-invalid @enderror"
                                       value="{{ old('jam_masuk', '07:30') }}"
                                       required>
                                @error('jam_masuk')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-6 col-sm-4">
                                <label for="jam_keluar" class="form-label">Jam Keluar <span class="text-danger">*</span></label>
                                <input type="time"
                                       name="jam_keluar"
                                       id="jam_keluar"
                                       class="form-control @error('jam_keluar') is-invalid @enderror"
                                       value="{{ old('jam_keluar', '16:00') }}"
                                       required>
                                @error('jam_keluar')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- KOLOM KANAN: Aktivitas Kinerja & Tombol Aksi -->
                    <div class="col-lg-7 ps-lg-4 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                                <div class="rounded-circle bg-danger bg-opacity-10 text-danger p-2 me-2.5 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                                    <i class="bi bi-pencil-square fs-6"></i>
                                </div>
                                <h6 class="fw-bold text-dark mb-0 small text-uppercase">2. Uraian Kinerja &amp; Hasil Pekerjaan</h6>
                            </div>

                            <div class="mb-3">
                                <label for="uraian" class="form-label">Uraian Tugas / Pekerjaan Hari Ini <span class="text-danger">*</span></label>
                                <textarea name="uraian"
                                          id="uraian"
                                          class="form-control @error('uraian') is-invalid @enderror"
                                          rows="3"
                                          required
                                          placeholder="Jelaskan secara ringkas aktivitas dan kegiatan kerja yang telah Anda laksanakan...">{{ old('uraian') }}</textarea>
                                @error('uraian')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="output" class="form-label">Output / Hasil Pekerjaan <span class="text-danger">*</span></label>
                                <textarea name="output"
                                          id="output"
                                          class="form-control @error('output') is-invalid @enderror"
                                          rows="2"
                                          required
                                          placeholder="Contoh: 1 Dokumen Berita Acara rapat, 10 berkas verifikasi logistik...">{{ old('output') }}</textarea>
                                @error('output')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Footer Aksi di Bawah Kolom Kanan -->
                        <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-2">
                            <a href="{{ route('laporan.riwayat') }}" class="btn btn-light text-secondary">
                                <i class="bi bi-arrow-left"></i> Batal &amp; Kembali
                            </a>

                            <button type="submit" id="btnSubmitHadir" class="btn btn-kpu px-4">
                                <span id="btnText"><i class="bi bi-send-fill"></i> Kirim Laporan Kinerja</span>
                                <span id="btnLoading" class="d-none">
                                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                    Menyimpan...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    @media (min-width: 992px) {
        .border-end-lg {
            border-right: 1px solid #e2e8f0;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var namaSelect = document.getElementById('nama');
        var nipInput = document.getElementById('nip');
        var jabatanInput = document.getElementById('jabatan');
        var form = document.getElementById('laporanHadirForm');
        var btnSubmit = document.getElementById('btnSubmitHadir');
        var btnText = document.getElementById('btnText');
        var btnLoading = document.getElementById('btnLoading');

        function updateKaryawanDetails() {
            var selected = namaSelect.options[namaSelect.selectedIndex];
            if (selected && selected.value) {
                nipInput.value = selected.getAttribute('data-nip') || '';
                jabatanInput.value = selected.getAttribute('data-jabatan') || '';
            }
        }

        namaSelect.addEventListener('change', updateKaryawanDetails);

        if (namaSelect.value && (!nipInput.value || !jabatanInput.value)) {
            updateKaryawanDetails();
        }

        form.addEventListener('submit', function() {
            btnSubmit.disabled = true;
            btnText.classList.add('d-none');
            btnLoading.classList.remove('d-none');
        });
    });
</script>
@endpush
