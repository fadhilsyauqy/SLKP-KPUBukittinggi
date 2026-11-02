@extends('layouts.app')

@section('title', 'Form Laporan Kinerja Harian - SLKP KPU Bukittinggi')
@section('page-title', 'Laporan Kinerja Harian')

@section('content')
<div class="container-fluid" style="max-width: 900px;">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <div class="rounded-circle bg-danger-subtle text-danger p-2 me-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                    <i class="bi bi-file-earmark-check-fill fs-5"></i>
                </div>
                <div>
                    <h5 class="mb-0 fw-bold text-dark">FORM LAPORAN KINERJA PEGAWAI HARIAN</h5>
                    <small class="text-muted">Laporkan kehadiran dan uraian hasil pekerjaan Anda setiap hari kerja</small>
                </div>
            </div>
            <a href="{{ route('laporan.tidakhadir') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-calendar-x me-1"></i> Form Izin / Sakit
            </a>
        </div>

        <div class="card-body p-4">
            <form id="laporanHadirForm" action="{{ route('laporan.hadir.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="nama" class="form-label fw-semibold">Nama Lengkap Pegawai <span class="text-danger">*</span></label>
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

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nip" class="form-label fw-semibold">NIP Pegawai <span class="text-danger">*</span></label>
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

                    <div class="col-md-6 mb-3">
                        <label for="jabatan" class="form-label fw-semibold">Jabatan Pegawai</label>
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

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="tanggal" class="form-label fw-semibold">Tanggal Kerja <span class="text-danger">*</span></label>
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

                    <div class="col-md-4 mb-3">
                        <label for="jam_masuk" class="form-label fw-semibold">Jam Masuk <span class="text-danger">*</span></label>
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

                    <div class="col-md-4 mb-3">
                        <label for="jam_keluar" class="form-label fw-semibold">Jam Keluar <span class="text-danger">*</span></label>
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

                <div class="mb-3">
                    <label for="uraian" class="form-label fw-semibold">Uraian Pekerjaan <span class="text-danger">*</span></label>
                    <textarea name="uraian"
                              id="uraian"
                              class="form-control @error('uraian') is-invalid @enderror"
                              rows="3"
                              required
                              placeholder="Jelaskan rincian aktivitas / kegiatan kerja yang telah Anda laksanakan hari ini...">{{ old('uraian') }}</textarea>
                    @error('uraian')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="output" class="form-label fw-semibold">Output / Hasil Pekerjaan <span class="text-danger">*</span></label>
                    <textarea name="output"
                              id="output"
                              class="form-control @error('output') is-invalid @enderror"
                              rows="2"
                              required
                              placeholder="Contoh: 1 Berkas notulensi rapat, 10 dokumen verifikasi, dsb...">{{ old('output') }}</textarea>
                    @error('output')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center pt-2 border-top">
                    <a href="{{ route('laporan.riwayat') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Batal & Lihat Riwayat
                    </a>

                    <button type="submit" id="btnSubmitHadir" class="btn btn-danger px-4 py-2 fw-semibold">
                        <span id="btnText"><i class="bi bi-send-fill me-1"></i> Kirim Laporan Kinerja</span>
                        <span id="btnLoading" class="d-none">
                            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                            Menyimpan & Menyinkronkan...
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

        // Run once on load in case of preselected option
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
