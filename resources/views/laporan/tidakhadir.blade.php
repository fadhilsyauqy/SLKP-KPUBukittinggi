@extends('layouts.app')

@section('title', 'Form Laporan Tidak Hadir - SLKP KPU Bukittinggi')
@section('page-title', 'Formulir Laporan Tidak Hadir')

@section('content')
<div class="container-fluid" style="max-width: 880px;">
    <!-- Page Header & Tab Switcher (Minimalist & Non-Redundant) -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h5 class="fw-bold text-dark mb-0.5">Laporan Tidak Hadir Kerja</h5>
            <p class="text-muted small mb-0">Pengajuan keterangan Izin, Cuti, Sakit, atau Perjalanan Dinas (Perjadin)</p>
        </div>
        <div class="tab-switcher">
            <a href="{{ route('laporan.hadir') }}" class="tab-item">
                <i class="bi bi-file-earmark-check"></i> Hadir
            </a>
            <a href="{{ route('laporan.tidakhadir') }}" class="tab-item active">
                <i class="bi bi-calendar-x"></i> Izin / Cuti / Sakit
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form id="laporanTidakHadirForm" action="{{ route('laporan.tidakhadir.store') }}" method="POST" enctype="multipart/form-data">
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

                <!-- Section: Jenis Keterangan & Tanggal -->
                <div class="mb-4">
                    <h6 class="fw-bold text-dark small text-uppercase mb-3 pb-2 border-bottom">
                        <i class="bi bi-calendar-event text-danger me-1"></i> Jenis &amp; Durasi Waktu
                    </h6>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="keterangan" class="form-label">Jenis Keterangan <span class="text-danger">*</span></label>
                            <select name="keterangan" id="keterangan" class="form-select @error('keterangan') is-invalid @enderror" required>
                                <option value="">-- Pilih Jenis --</option>
                                <option value="Izin" {{ old('keterangan') == 'Izin' ? 'selected' : '' }}>Izin</option>
                                <option value="Cuti" {{ old('keterangan') == 'Cuti' ? 'selected' : '' }}>Cuti</option>
                                <option value="Sakit" {{ old('keterangan') == 'Sakit' ? 'selected' : '' }}>Sakit</option>
                                <option value="Perjadin" {{ old('keterangan') == 'Perjadin' ? 'selected' : '' }}>Perjalanan Dinas (Perjadin)</option>
                            </select>
                            @error('keterangan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label for="tanggal_in" class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
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

                        <div class="col-md-4">
                            <label for="tanggal_out" class="form-label">Tanggal Selesai <span class="text-danger">*</span></label>
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
                    </div>
                </div>

                <!-- Section: Alasan & Lampiran -->
                <div class="mb-4">
                    <h6 class="fw-bold text-dark small text-uppercase mb-3 pb-2 border-bottom">
                        <i class="bi bi-paperclip text-danger me-1"></i> Alasan &amp; Bukti Lampiran
                    </h6>

                    <div class="mb-3">
                        <label for="alasan" class="form-label">Alasan / Penjelasan Rinci <span class="text-danger">*</span></label>
                        <textarea name="alasan"
                                  id="alasan"
                                  class="form-control @error('alasan') is-invalid @enderror"
                                  rows="3"
                                  required
                                  placeholder="Jelaskan alasan ketidakhadiran Anda secara terperinci...">{{ old('alasan') }}</textarea>
                        @error('alasan')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-2">
                        <label for="attach" class="form-label">Dokumen Bukti Lampiran <span class="text-muted fw-normal">(Opsional)</span></label>
                        <input type="file"
                               name="attach"
                               id="attach"
                               class="form-control @error('attach') is-invalid @enderror"
                               accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                        <div class="form-text text-muted small">Format: PDF, JPG, PNG, atau DOCX (Maksimal 5MB)</div>
                        @error('attach')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Action Footer (Single Primary & Subtle Cancel) -->
                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <a href="{{ route('laporan.riwayat') }}" class="btn btn-light text-secondary">
                        <i class="bi bi-arrow-left"></i> Batal &amp; Kembali
                    </a>

                    <button type="submit" id="btnSubmitTidakHadir" class="btn btn-kpu px-4">
                        <span id="btnText"><i class="bi bi-send-fill"></i> Kirim Laporan</span>
                        <span id="btnLoading" class="d-none">
                            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                            Mengunggah...
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
