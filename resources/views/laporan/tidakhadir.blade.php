@extends('layouts.app')

@section('title', 'Form Laporan Tidak Hadir - SLKP KPU Bukittinggi')
@section('page-title', 'Laporan Tidak Hadir Kerja')

@section('content')
<div class="container-fluid" style="max-width: 900px;">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <div class="rounded-circle bg-primary-subtle text-primary p-2 me-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                    <i class="bi bi-calendar-x-fill fs-5"></i>
                </div>
                <div>
                    <h5 class="mb-0 fw-bold text-dark">FORM LAPORAN TIDAK HADIR KERJA</h5>
                    <small class="text-muted">Pengajuan keterangan Izin, Cuti, Sakit, atau Perjalanan Dinas (Perjadin)</small>
                </div>
            </div>
            <a href="{{ route('laporan.hadir') }}" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-file-earmark-check me-1"></i> Form Hadir
            </a>
        </div>

        <div class="card-body p-4">
            <form id="laporanTidakHadirForm" action="{{ route('laporan.tidakhadir.store') }}" method="POST" enctype="multipart/form-data">
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

                <div class="mb-3">
                    <label for="keterangan" class="form-label fw-semibold">Jenis Keterangan <span class="text-danger">*</span></label>
                    <select name="keterangan" id="keterangan" class="form-select @error('keterangan') is-invalid @enderror" required>
                        <option value="">-- Pilih Jenis Keterangan --</option>
                        @foreach ($keteranganOptions as $opt)
                            <option value="{{ $opt }}" {{ old('keterangan') == $opt ? 'selected' : '' }}>
                                {{ $opt }}
                            </option>
                        @endforeach
                    </select>
                    @error('keterangan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-5 mb-3">
                        <label for="tanggal_in" class="form-label fw-semibold">Tanggal Mulai <span class="text-danger">*</span></label>
                        <input type="date"
                               name="tanggal_in"
                               id="tanggal_in"
                               class="form-control @error('tanggal_in') is-invalid @enderror"
                               value="{{ old('tanggal_in', date('Y-m-d')) }}"
                               required>
                        @error('tanggal_in')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-5 mb-3">
                        <label for="tanggal_out" class="form-label fw-semibold">Tanggal Selesai <span class="text-danger">*</span></label>
                        <input type="date"
                               name="tanggal_out"
                               id="tanggal_out"
                               class="form-control @error('tanggal_out') is-invalid @enderror"
                               value="{{ old('tanggal_out', date('Y-m-d')) }}"
                               required>
                        @error('tanggal_out')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-2 mb-3">
                        <label for="lama_hari" class="form-label fw-semibold">Durasi</label>
                        <input type="text"
                               name="lama_hari"
                               id="lama_hari"
                               class="form-control bg-light text-center fw-bold"
                               value="{{ old('lama_hari', '1 Hari') }}"
                               readonly>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="alasan" class="form-label fw-semibold">Alasan / Rincian Keterangan <span class="text-danger">*</span></label>
                    <textarea name="alasan"
                              id="alasan"
                              class="form-control @error('alasan') is-invalid @enderror"
                              rows="3"
                              required
                              placeholder="Jelaskan alasan ketidakhadiran Anda secara jelas...">{{ old('alasan') }}</textarea>
                    @error('alasan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="attach" class="form-label fw-semibold">Bukti Lampiran (Surat Dokter / Dokumen Izin / Tugas)</label>
                    <input type="file"
                           name="attach"
                           id="attach"
                           class="form-control @error('attach') is-invalid @enderror"
                           accept=".jpg,.jpeg,.png,.pdf">
                    <small class="text-muted d-block mt-1">Opsional. Format yang didukung: JPG, PNG, PDF (Maksimal 5MB)</small>
                    @error('attach')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center pt-2 border-top">
                    <a href="{{ route('laporan.riwayat') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Batal & Lihat Riwayat
                    </a>

                    <button type="submit" id="btnSubmitTidakHadir" class="btn btn-primary px-4 py-2 fw-semibold">
                        <span id="btnText"><i class="bi bi-send-fill me-1"></i> Kirim Laporan Tidak Hadir</span>
                        <span id="btnLoading" class="d-none">
                            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                            Mengirim & Mengunggah...
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
        var tInInput = document.getElementById('tanggal_in');
        var tOutInput = document.getElementById('tanggal_out');
        var lamaHariInput = document.getElementById('lama_hari');
        var form = document.getElementById('laporanTidakHadirForm');
        var btnSubmit = document.getElementById('btnSubmitTidakHadir');
        var btnText = document.getElementById('btnText');
        var btnLoading = document.getElementById('btnLoading');

        function updateKaryawanDetails() {
            var selected = namaSelect.options[namaSelect.selectedIndex];
            if (selected && selected.value) {
                nipInput.value = selected.getAttribute('data-nip') || '';
                jabatanInput.value = selected.getAttribute('data-jabatan') || '';
            }
        }

        function calculateDays() {
            var tIn = tInInput.value;
            var tOut = tOutInput.value;
            if (tIn && tOut) {
                var d1 = new Date(tIn);
                var d2 = new Date(tOut);
                var diffTime = d2.getTime() - d1.getTime();
                var diffDays = Math.ceil(diffTime / (1000 * 3600 * 24)) + 1;
                lamaHariInput.value = diffDays > 0 ? diffDays + ' Hari' : '1 Hari';
            }
        }

        namaSelect.addEventListener('change', updateKaryawanDetails);
        tInInput.addEventListener('change', calculateDays);
        tOutInput.addEventListener('change', calculateDays);

        if (namaSelect.value && (!nipInput.value || !jabatanInput.value)) {
            updateKaryawanDetails();
        }
        calculateDays();

        form.addEventListener('submit', function() {
            btnSubmit.disabled = true;
            btnText.classList.add('d-none');
            btnLoading.classList.remove('d-none');
        });
    });
</script>
@endpush
