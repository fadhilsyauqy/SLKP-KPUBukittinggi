@extends('layouts.app')

@section('title', 'Form Laporan Tidak Hadir - SLKP KPU Bukittinggi')
@section('page-title', 'Formulir Laporan Tidak Hadir')

@section('content')
<div class="container-fluid px-0 px-md-2">
    <!-- Page Header & Tab Switcher (Full Width & Clean) -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h5 class="fw-bold text-dark mb-0.5">FORM LAPORAN TIDAK HADIR KERJA</h5>
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

    <!-- Full-Width Horizontal Form Card (Single Screen Viewport) -->
    <div class="card border-0 shadow-sm w-100">
        <div class="card-body p-3 p-lg-4">
            <!-- Alert Pemulihan Draf Otomatis (TASK-1.2) -->
            <div id="draftAlertNotice" class="alert alert-warning bg-warning bg-opacity-10 border border-warning border-opacity-25 py-2 px-3 small d-flex align-items-center justify-content-between mb-3 d-none" role="alert">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-clock-history text-warning-emphasis fs-6"></i>
                    <div>
                        <strong class="text-warning-emphasis">Draf permohonan sebelumnya dipulihkan.</strong>
                        <span id="draftAlertTime" class="text-muted ms-1"></span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" id="btnDismissDraftNotice" class="btn-close" aria-label="Tutup" style="font-size: 0.7rem;"></button>
                </div>
            </div>

            <form id="laporanTidakHadirForm" action="{{ route('laporan.tidakhadir.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row g-4">
                    <!-- KOLOM KIRI: Identitas Pegawai & Jenis/Waktu -->
                    <div class="col-lg-5 border-end-lg pe-lg-4">
                        <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                            <div class="rounded-circle bg-warning bg-opacity-10 text-warning-emphasis p-2 me-2.5 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                                <i class="bi bi-person-badge fs-6"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-0 small text-uppercase">1. Identitas &amp; Waktu Tidak Hadir</h6>
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

                        <!-- Jenis & Rentang Tanggal (3 Kolom Berdampingan) -->
                        <div class="row g-2">
                            <div class="col-12 col-sm-4">
                                <label for="keterangan" class="form-label">Jenis <span class="text-danger">*</span></label>
                                <select name="keterangan" id="keterangan" class="form-select @error('keterangan') is-invalid @enderror" required>
                                    <option value="">-- Pilih --</option>
                                    <option value="Izin" {{ old('keterangan') == 'Izin' ? 'selected' : '' }}>Izin</option>
                                    <option value="Cuti" {{ old('keterangan') == 'Cuti' ? 'selected' : '' }}>Cuti</option>
                                    <option value="Sakit" {{ old('keterangan') == 'Sakit' ? 'selected' : '' }}>Sakit</option>
                                    <option value="Perjadin" {{ old('keterangan') == 'Perjadin' ? 'selected' : '' }}>Perjadin</option>
                                </select>
                                @error('keterangan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-6 col-sm-4">
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

                            <div class="col-6 col-sm-4">
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

                    <!-- KOLOM KANAN: Alasan, Lampiran & Tombol Aksi -->
                    <div class="col-lg-7 ps-lg-4 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                                <div class="rounded-circle bg-warning bg-opacity-10 text-warning-emphasis p-2 me-2.5 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                                    <i class="bi bi-paperclip fs-6"></i>
                                </div>
                                <h6 class="fw-bold text-dark mb-0 small text-uppercase">2. Alasan &amp; Berkas Lampiran</h6>
                            </div>

                            <div class="mb-3">
                                <label for="alasan" class="form-label">Alasan / Penjelasan Rinci <span class="text-danger">*</span></label>
                                <textarea name="alasan"
                                          id="alasan"
                                          class="form-control @error('alasan') is-invalid @enderror"
                                          rows="3"
                                          required
                                          placeholder="Jelaskan alasan izin / sakit / cuti / perjalanan dinas secara lengkap...">{{ old('alasan') }}</textarea>
                                @error('alasan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="attach" class="form-label">Dokumen Bukti Lampiran <span class="text-muted fw-normal small">(Surat Sakit/Tugas/Cuti - Opsional)</span></label>
                                <input type="file"
                                       name="attach"
                                       id="attach"
                                       class="form-control @error('attach') is-invalid @enderror"
                                       accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                                <div class="form-text text-muted small mt-1">Format: PDF, JPG, PNG, atau DOCX (Maksimal 5MB)</div>
                                @error('attach')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Footer Aksi di Bawah Kolom Kanan -->
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pt-3 border-top mt-2">
                            <div class="d-flex align-items-center gap-2">
                                <a href="{{ route('laporan.riwayat') }}" class="btn btn-light text-secondary">
                                    <i class="bi bi-arrow-left"></i> Batal &amp; Kembali
                                </a>
                                <button type="button" id="btnClearDraftBtn" class="btn btn-outline-secondary btn-sm d-none" title="Hapus draf yang tersimpan di browser">
                                    <i class="bi bi-eraser"></i> Bersihkan Draf
                                </button>
                            </div>

                            <div class="d-flex align-items-center gap-2 ms-auto">
                                <small id="draftAutoSaveStatus" class="text-muted d-none" style="font-size: 0.775rem;"></small>
                                <button type="submit" id="btnSubmitTidakHadir" class="btn btn-kpu px-4">
                                    <span id="btnText"><i class="bi bi-send-fill"></i> Kirim Laporan</span>
                                    <span id="btnLoading" class="d-none">
                                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                        Mengunggah...
                                    </span>
                                </button>
                            </div>
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

        // ==========================================
        // Fitur Auto-Save Draft LocalStorage (TASK-1.2)
        // ==========================================
        var STORAGE_KEY = 'slkp_draft_tidakhadir_{{ auth()->id() ?? 'guest' }}';
        var keteranganSelect = document.getElementById('keterangan');
        var tanggalInInput = document.getElementById('tanggal_in');
        var tanggalOutInput = document.getElementById('tanggal_out');
        var alasanTextarea = document.getElementById('alasan');
        var draftAlertNotice = document.getElementById('draftAlertNotice');
        var draftAlertTime = document.getElementById('draftAlertTime');
        var btnDismissDraftNotice = document.getElementById('btnDismissDraftNotice');
        var btnClearDraftBtn = document.getElementById('btnClearDraftBtn');
        var draftAutoSaveStatus = document.getElementById('draftAutoSaveStatus');
        var saveDraftTimeout = null;

        function saveDraft() {
            try {
                var alasanVal = alasanTextarea ? alasanTextarea.value.trim() : '';
                var ketVal = keteranganSelect ? keteranganSelect.value.trim() : '';

                if (alasanVal !== '' || ketVal !== '') {
                    var now = new Date();
                    var timeFormatted = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                    var draftData = {
                        nama: namaSelect ? namaSelect.value : '',
                        keterangan: ketVal,
                        tanggal_in: tanggalInInput ? tanggalInInput.value : '',
                        tanggal_out: tanggalOutInput ? tanggalOutInput.value : '',
                        alasan: alasanTextarea ? alasanTextarea.value : '',
                        savedAt: now.toISOString(),
                        savedAtDisplay: timeFormatted
                    };
                    localStorage.setItem(STORAGE_KEY, JSON.stringify(draftData));

                    if (draftAutoSaveStatus) {
                        draftAutoSaveStatus.innerHTML = '<i class="bi bi-check2-circle text-success"></i> Draf tersimpan (' + timeFormatted + ')';
                        draftAutoSaveStatus.classList.remove('d-none');
                    }
                    if (btnClearDraftBtn) {
                        btnClearDraftBtn.classList.remove('d-none');
                    }
                } else {
                    localStorage.removeItem(STORAGE_KEY);
                    if (draftAutoSaveStatus) {
                        draftAutoSaveStatus.classList.add('d-none');
                    }
                    if (btnClearDraftBtn) {
                        btnClearDraftBtn.classList.add('d-none');
                    }
                }
            } catch (e) {
                console.warn('Gagal menyimpan draf ke localStorage:', e);
            }
        }

        function triggerDebouncedAutoSave() {
            if (saveDraftTimeout) {
                clearTimeout(saveDraftTimeout);
            }
            if (draftAutoSaveStatus) {
                draftAutoSaveStatus.innerHTML = '<span class="spinner-border spinner-border-sm text-muted" role="status" style="width: 0.65rem; height: 0.65rem;"></span> Menyimpan draf...';
                draftAutoSaveStatus.classList.remove('d-none');
            }
            saveDraftTimeout = setTimeout(function() {
                saveDraft();
            }, 500);
        }

        function loadDraft() {
            try {
                var rawDraft = localStorage.getItem(STORAGE_KEY);
                if (!rawDraft) {
                    return;
                }

                var draft = JSON.parse(rawDraft);
                if (!draft) {
                    return;
                }

                var isAlasanEmpty = !alasanTextarea || alasanTextarea.value.trim() === '';

                if (isAlasanEmpty && (draft.alasan || draft.keterangan)) {
                    if (draft.alasan && alasanTextarea) {
                        alasanTextarea.value = draft.alasan;
                    }
                    if (draft.keterangan && keteranganSelect && (!keteranganSelect.value || keteranganSelect.value === '')) {
                        keteranganSelect.value = draft.keterangan;
                    }
                    if (draft.tanggal_in && tanggalInInput) {
                        tanggalInInput.value = draft.tanggal_in;
                    }
                    if (draft.tanggal_out && tanggalOutInput) {
                        tanggalOutInput.value = draft.tanggal_out;
                    }
                    if (draft.nama && namaSelect && !namaSelect.disabled && (!namaSelect.value || namaSelect.value === '')) {
                        namaSelect.value = draft.nama;
                        updateKaryawanDetails();
                    }

                    if (draftAlertNotice) {
                        if (draftAlertTime) {
                            var waktuSimpan = draft.savedAtDisplay ? 'pukul ' + draft.savedAtDisplay + ' WIB' : '';
                            draftAlertTime.textContent = waktuSimpan ? '(' + waktuSimpan + ')' : '';
                        }
                        draftAlertNotice.classList.remove('d-none');
                    }

                    if (btnClearDraftBtn) {
                        btnClearDraftBtn.classList.remove('d-none');
                    }
                    if (draftAutoSaveStatus) {
                        draftAutoSaveStatus.innerHTML = '<i class="bi bi-clock-history text-warning-emphasis"></i> Draf dipulihkan';
                        draftAutoSaveStatus.classList.remove('d-none');
                    }
                } else if (!isAlasanEmpty) {
                    if (btnClearDraftBtn) {
                        btnClearDraftBtn.classList.remove('d-none');
                    }
                }
            } catch (e) {
                console.warn('Gagal memuat draf dari localStorage:', e);
            }
        }

        function clearDraft(confirmPrompt) {
            if (confirmPrompt) {
                if (!confirm('Apakah Anda yakin ingin menghapus draf permohonan ini? Semua isian yang belum dikirim akan dikosongkan.')) {
                    return;
                }
            }

            try {
                localStorage.removeItem(STORAGE_KEY);
            } catch (e) {
                // ignore
            }

            if (alasanTextarea) alasanTextarea.value = '';
            if (keteranganSelect) keteranganSelect.value = '';
            var today = new Date().toISOString().split('T')[0];
            if (tanggalInInput) tanggalInInput.value = today;
            if (tanggalOutInput) tanggalOutInput.value = today;

            if (draftAlertNotice) {
                draftAlertNotice.classList.add('d-none');
            }
            if (btnClearDraftBtn) {
                btnClearDraftBtn.classList.add('d-none');
            }
            if (draftAutoSaveStatus) {
                draftAutoSaveStatus.innerHTML = '<span class="text-secondary">Draf dibersihkan</span>';
                draftAutoSaveStatus.classList.remove('d-none');
                setTimeout(function() {
                    draftAutoSaveStatus.classList.add('d-none');
                }, 3000);
            }
        }

        if (alasanTextarea) alasanTextarea.addEventListener('input', triggerDebouncedAutoSave);
        if (keteranganSelect) keteranganSelect.addEventListener('change', triggerDebouncedAutoSave);
        if (tanggalInInput) tanggalInInput.addEventListener('change', triggerDebouncedAutoSave);
        if (tanggalOutInput) tanggalOutInput.addEventListener('change', triggerDebouncedAutoSave);
        if (namaSelect) namaSelect.addEventListener('change', triggerDebouncedAutoSave);

        if (btnClearDraftBtn) {
            btnClearDraftBtn.addEventListener('click', function() {
                clearDraft(true);
            });
        }

        if (btnDismissDraftNotice) {
            btnDismissDraftNotice.addEventListener('click', function() {
                if (draftAlertNotice) {
                    draftAlertNotice.classList.add('d-none');
                }
            });
        }

        form.addEventListener('submit', function() {
            try {
                localStorage.removeItem(STORAGE_KEY);
            } catch (e) {
                // ignore
            }
            btnSubmit.disabled = true;
            btnText.classList.add('d-none');
            btnLoading.classList.remove('d-none');
        });

        loadDraft();
    });
</script>
@endpush
