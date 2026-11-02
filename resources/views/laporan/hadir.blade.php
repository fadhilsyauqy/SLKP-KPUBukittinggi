@extends('layouts.app')

@section('title', 'Form Laporan Hadir - SLKP KPU Bukittinggi')
@section('page-title', 'Formulir Laporan Hadir')

@section('content')
<div class="container-fluid" style="max-width: 880px;">
    <!-- Page Header & Tab Switcher (Minimalist & Non-Redundant) -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
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

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form id="laporanHadirForm" action="{{ route('laporan.hadir.store') }}" method="POST">
                @csrf

                <!-- Section: Identitas Pegawai -->
                <div class="mb-4">
                    <h6 class="fw-bold text-dark small text-uppercase mb-3 pb-2 border-bottom">
                        <i class="bi bi-person-badge text-danger me-1"></i> Identitas Pegawai
                    </h6>

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

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="nip" class="form-label">NIP Pegawai <span class="text-danger">*</span></label>
                            <input type="text"
                                   name="nip"
                                   id="nip"
                                   class="form-control bg-light @error('nip') is-invalid @enderror"
                                   value="{{ old('nip', $currentKaryawan?->nip) }}"
                                   readonly
                                   required
                                   placeholder="Terisi otomatis saat memilih nama">
                            @error('nip')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="jabatan" class="form-label">Jabatan Pegawai</label>
                            <input type="text"
                                   name="jabatan"
                                   id="jabatan"
                                   class="form-control bg-light @error('jabatan') is-invalid @enderror"
                                   value="{{ old('jabatan', $currentKaryawan?->jabatan?->nama_jabatan) }}"
                                   readonly
                                   placeholder="Terisi otomatis saat memilih nama">
                            @error('jabatan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Section: Waktu & Tanggal Kerja -->
                <div class="mb-4">
                    <h6 class="fw-bold text-dark small text-uppercase mb-3 pb-2 border-bottom">
                        <i class="bi bi-clock-history text-danger me-1"></i> Waktu Presensi
                    </h6>

                    <div class="row g-3">
                        <div class="col-md-4">
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

                        <div class="col-md-4">
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

                        <div class="col-md-4">
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

                <!-- Section: Uraian Kinerja -->
                <div class="mb-4">
                    <h6 class="fw-bold text-dark small text-uppercase mb-3 pb-2 border-bottom">
                        <i class="bi bi-pencil-square text-danger me-1"></i> Aktivitas &amp; Hasil Kinerja
                    </h6>

                    <div class="mb-3">
                        <label for="uraian" class="form-label">Uraian Pekerjaan Hari Ini <span class="text-danger">*</span></label>
                        <textarea name="uraian"
                                  id="uraian"
                                  class="form-control @error('uraian') is-invalid @enderror"
                                  rows="3"
                                  required
                                  placeholder="Tuliskan rincian tugas dan aktivitas kerja yang telah Anda selesaikan...">{{ old('uraian') }}</textarea>
                        @error('uraian')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-2">
                        <label for="output" class="form-label">Output / Hasil Pekerjaan <span class="text-danger">*</span></label>
                        <textarea name="output"
                                  id="output"
                                  class="form-control @error('output') is-invalid @enderror"
                                  rows="2"
                                  required
                                  placeholder="Contoh: 1 Dokumen Berita Acara, 5 Berkas verifikasi pemilu, dsb...">{{ old('output') }}</textarea>
                        @error('output')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Action Footer (Single Primary & Subtle Cancel) -->
                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <a href="{{ route('laporan.riwayat') }}" class="btn btn-light text-secondary">
                        <i class="bi bi-arrow-left"></i> Batal &amp; Kembali
                    </a>

                    <button type="submit" id="btnSubmitHadir" class="btn btn-kpu px-4">
                        <span id="btnText"><i class="bi bi-send-fill"></i> Kirim Laporan</span>
                        <span id="btnLoading" class="d-none">
                            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                            Menyimpan...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

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
