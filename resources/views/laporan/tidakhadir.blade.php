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
                                <label for="attach" class="form-label fw-semibold">Dokumen Bukti Lampiran <span class="text-muted fw-normal small">(Surat Sakit/Tugas/Cuti - Opsional)</span></label>
                                <input type="file"
                                       name="attach"
                                       id="attach"
                                       class="form-control @error('attach') is-invalid @enderror"
                                       accept=".jpg,.jpeg,.png,.pdf">
                                <div class="form-text text-muted small mt-1">Format: PDF, JPG, PNG (Maksimal 5MB). Foto kamera ponsel otomatis dioptimalkan ukurannya.</div>
                                @error('attach')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror

                                <!-- Indikator Loading Kompresi (TASK-1.5) -->
                                <div id="attachCompressingNotice" class="alert alert-info py-2 px-3 small mt-2 d-none d-flex align-items-center gap-2" role="status">
                                    <span class="spinner-border spinner-border-sm text-info flex-shrink-0" role="status" aria-hidden="true"></span>
                                    <span>Mengoptimalkan dan mengompresi ukuran gambar...</span>
                                </div>

                                <!-- Alert Error Validasi File di Client (TASK-1.5) -->
                                <div id="attachSizeErrorNotice" class="alert alert-danger py-2 px-3 small mt-2 d-none d-flex align-items-center justify-content-between" role="alert">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-exclamation-triangle-fill text-danger flex-shrink-0 fs-6"></i>
                                        <span id="attachSizeErrorMessage">Ukuran file melebihi batas 5MB. Silakan pilih file yang lebih kecil.</span>
                                    </div>
                                    <button type="button" id="btnDismissAttachError" class="btn-close" aria-label="Tutup" style="font-size: 0.7rem;"></button>
                                </div>

                                <!-- UI Preview Thumbnail & Card (TASK-1.5) -->
                                <div id="attachPreviewContainer" class="card border rounded-3 p-2.5 mt-2 bg-light bg-opacity-50 d-none">
                                    <div class="d-flex align-items-center justify-content-between gap-2">
                                        <div class="d-flex align-items-center gap-2.5 overflow-hidden">
                                            <!-- Thumbnail Preview (Image) / Icon (PDF) -->
                                            <div id="attachThumbWrapper" class="flex-shrink-0 rounded border bg-white d-flex align-items-center justify-content-center overflow-hidden" style="width: 52px; height: 52px;">
                                                <img id="attachImagePreview" src="" alt="Pratinjau Bukti" class="w-100 h-100 object-fit-cover d-none">
                                                <i id="attachPdfIcon" class="bi bi-file-earmark-pdf-fill text-danger fs-2 d-none"></i>
                                            </div>

                                            <!-- Detail Info Berkas -->
                                            <div class="overflow-hidden">
                                                <div id="attachFileName" class="fw-semibold text-dark text-truncate small" style="max-width: 250px;">-</div>
                                                <div class="d-flex flex-wrap align-items-center gap-1 mt-1">
                                                    <span id="attachFileTypeBadge" class="badge bg-secondary bg-opacity-10 text-secondary border py-0.5 px-1.5" style="font-size: 0.7rem;">BERKAS</span>
                                                    <span id="attachFileSizeBadge" class="badge bg-light text-muted border py-0.5 px-1.5" style="font-size: 0.7rem;">0 KB</span>
                                                    <span id="attachCompressionBadge" class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-0.5 px-1.5 d-none" style="font-size: 0.7rem;">
                                                        <i class="bi bi-lightning-charge-fill me-0.5"></i> Terkompresi
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Tombol Hapus Lampiran -->
                                        <button type="button" id="btnRemoveAttach" class="btn btn-outline-danger btn-sm flex-shrink-0 py-1 px-2.5" title="Hapus berkas lampiran">
                                            <i class="bi bi-trash3 me-1"></i> <span class="d-none d-sm-inline">Hapus Lampiran</span><span class="d-inline d-sm-none">Hapus</span>
                                        </button>
                                    </div>
                                </div>
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
            resetAttachment();

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

        // ==========================================
        // Fitur Pratinjau & Kompresi Foto Lampiran (TASK-1.5)
        // ==========================================
        var attachInput = document.getElementById('attach');
        var attachPreviewContainer = document.getElementById('attachPreviewContainer');
        var attachImagePreview = document.getElementById('attachImagePreview');
        var attachPdfIcon = document.getElementById('attachPdfIcon');
        var attachFileName = document.getElementById('attachFileName');
        var attachFileTypeBadge = document.getElementById('attachFileTypeBadge');
        var attachFileSizeBadge = document.getElementById('attachFileSizeBadge');
        var attachCompressionBadge = document.getElementById('attachCompressionBadge');
        var attachCompressingNotice = document.getElementById('attachCompressingNotice');
        var attachSizeErrorNotice = document.getElementById('attachSizeErrorNotice');
        var attachSizeErrorMessage = document.getElementById('attachSizeErrorMessage');
        var btnDismissAttachError = document.getElementById('btnDismissAttachError');
        var btnRemoveAttach = document.getElementById('btnRemoveAttach');
        var currentPreviewUrl = null;

        function formatBytes(bytes) {
            if (!bytes || bytes === 0) return '0 B';
            var k = 1024;
            var sizes = ['B', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        }

        function showAttachError(message) {
            if (attachSizeErrorMessage) {
                attachSizeErrorMessage.textContent = message;
            }
            if (attachSizeErrorNotice) {
                attachSizeErrorNotice.classList.remove('d-none');
            }
        }

        function hideAttachError() {
            if (attachSizeErrorNotice) {
                attachSizeErrorNotice.classList.add('d-none');
            }
        }

        function resetAttachment() {
            if (attachInput) {
                attachInput.value = '';
            }
            if (currentPreviewUrl) {
                try {
                    URL.revokeObjectURL(currentPreviewUrl);
                } catch (e) {}
                currentPreviewUrl = null;
            }
            if (attachImagePreview) {
                attachImagePreview.src = '';
                attachImagePreview.classList.add('d-none');
            }
            if (attachPdfIcon) {
                attachPdfIcon.classList.add('d-none');
            }
            if (attachPreviewContainer) {
                attachPreviewContainer.classList.add('d-none');
            }
            if (attachCompressingNotice) {
                attachCompressingNotice.classList.add('d-none');
            }
            if (attachCompressionBadge) {
                attachCompressionBadge.classList.add('d-none');
            }
            hideAttachError();
        }

        function compressImage(file, maxDimension, quality, callback) {
            if (!file.type.match(/^image\/(jpeg|jpg|png|webp)$/i)) {
                callback(file, null);
                return;
            }

            var reader = new FileReader();
            reader.onload = function(e) {
                var img = new Image();
                img.onload = function() {
                    var width = img.width;
                    var height = img.height;

                    if (width > maxDimension || height > maxDimension) {
                        if (width > height) {
                            height = Math.round((height * maxDimension) / width);
                            width = maxDimension;
                        } else {
                            width = Math.round((width * maxDimension) / height);
                            height = maxDimension;
                        }
                    }

                    var canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;
                    var ctx = canvas.getContext('2d');

                    ctx.fillStyle = '#FFFFFF';
                    ctx.fillRect(0, 0, width, height);
                    ctx.drawImage(img, 0, 0, width, height);

                    var mimeType = 'image/jpeg';
                    canvas.toBlob(function(blob) {
                        if (!blob) {
                            callback(file, null);
                            return;
                        }

                        if (blob.size >= file.size && file.size <= 5 * 1024 * 1024) {
                            callback(file, {
                                originalSize: file.size,
                                compressedSize: file.size,
                                compressed: false
                            });
                            return;
                        }

                        var baseName = file.name.replace(/\.[^/.]+$/, "");
                        var newFileName = baseName + ".jpg";
                        var compressedFile;
                        try {
                            compressedFile = new File([blob], newFileName, {
                                type: mimeType,
                                lastModified: Date.now()
                            });
                        } catch (err) {
                            blob.name = newFileName;
                            compressedFile = blob;
                        }

                        callback(compressedFile, {
                            originalSize: file.size,
                            compressedSize: blob.size,
                            compressed: true
                        });
                    }, mimeType, quality);
                };
                img.onerror = function() {
                    callback(file, null);
                };
                img.src = e.target.result;
            };
            reader.onerror = function() {
                callback(file, null);
            };
            reader.readAsDataURL(file);
        }

        if (attachInput) {
            attachInput.addEventListener('change', function() {
                hideAttachError();

                if (!this.files || this.files.length === 0) {
                    resetAttachment();
                    return;
                }

                var file = this.files[0];
                var fileName = file.name;
                var ext = fileName.split('.').pop().toLowerCase();
                var allowedExts = ['jpg', 'jpeg', 'png', 'pdf'];

                if (allowedExts.indexOf(ext) === -1) {
                    showAttachError('Format berkas "' + ext.toUpperCase() + '" tidak didukung. Harap pilih file JPG, PNG, atau PDF.');
                    this.value = '';
                    if (attachPreviewContainer) attachPreviewContainer.classList.add('d-none');
                    return;
                }

                // File PDF
                if (file.type === 'application/pdf' || ext === 'pdf') {
                    if (file.size > 5 * 1024 * 1024) {
                        showAttachError('Ukuran file PDF melebihi batas 5MB (' + formatBytes(file.size) + '). Silakan pilih file dokumen yang lebih kecil.');
                        this.value = '';
                        if (attachPreviewContainer) attachPreviewContainer.classList.add('d-none');
                        return;
                    }

                    if (currentPreviewUrl) {
                        try { URL.revokeObjectURL(currentPreviewUrl); } catch (e) {}
                        currentPreviewUrl = null;
                    }

                    if (attachImagePreview) attachImagePreview.classList.add('d-none');
                    if (attachPdfIcon) attachPdfIcon.classList.remove('d-none');
                    if (attachFileName) attachFileName.textContent = fileName;
                    if (attachFileTypeBadge) attachFileTypeBadge.textContent = 'PDF';
                    if (attachFileSizeBadge) attachFileSizeBadge.textContent = formatBytes(file.size);
                    if (attachCompressionBadge) attachCompressionBadge.classList.add('d-none');
                    if (attachPreviewContainer) attachPreviewContainer.classList.remove('d-none');
                    return;
                }

                // File Gambar (JPG / PNG / WEBP)
                if (file.type.match(/^image\//i) || ['jpg', 'jpeg', 'png'].indexOf(ext) !== -1) {
                    if (attachCompressingNotice) {
                        attachCompressingNotice.classList.remove('d-none');
                    }
                    if (attachPreviewContainer) {
                        attachPreviewContainer.classList.add('d-none');
                    }

                    compressImage(file, 1800, 0.82, function(resultFile, info) {
                        if (attachCompressingNotice) {
                            attachCompressingNotice.classList.add('d-none');
                        }

                        if (info && info.compressed && window.DataTransfer) {
                            try {
                                var dt = new DataTransfer();
                                dt.items.add(resultFile);
                                attachInput.files = dt.files;
                            } catch (dtError) {
                                console.warn('DataTransfer update skipped:', dtError);
                            }
                        }

                        if (resultFile.size > 5 * 1024 * 1024) {
                            showAttachError('Ukuran gambar setelah kompresi masih melebihi 5MB (' + formatBytes(resultFile.size) + '). Harap gunakan gambar beresolusi lebih rendah.');
                            attachInput.value = '';
                            return;
                        }

                        if (currentPreviewUrl) {
                            try { URL.revokeObjectURL(currentPreviewUrl); } catch (e) {}
                        }
                        try {
                            currentPreviewUrl = URL.createObjectURL(resultFile);
                            if (attachImagePreview) {
                                attachImagePreview.src = currentPreviewUrl;
                                attachImagePreview.classList.remove('d-none');
                            }
                        } catch (urlErr) {
                            console.warn('ObjectURL error:', urlErr);
                        }

                        if (attachPdfIcon) attachPdfIcon.classList.add('d-none');
                        if (attachFileName) attachFileName.textContent = resultFile.name || fileName;
                        if (attachFileTypeBadge) attachFileTypeBadge.textContent = 'GAMBAR';
                        if (attachFileSizeBadge) attachFileSizeBadge.textContent = formatBytes(resultFile.size);

                        if (info && info.compressed && info.originalSize > info.compressedSize) {
                            var savedPercent = Math.round(((info.originalSize - info.compressedSize) / info.originalSize) * 100);
                            if (savedPercent > 0 && attachCompressionBadge) {
                                attachCompressionBadge.innerHTML = '<i class="bi bi-lightning-charge-fill me-0.5"></i> Hemat ' + savedPercent + '% (' + formatBytes(info.originalSize) + ' &rarr; ' + formatBytes(info.compressedSize) + ')';
                                attachCompressionBadge.classList.remove('d-none');
                            }
                        } else if (attachCompressionBadge) {
                            attachCompressionBadge.classList.add('d-none');
                        }

                        if (attachPreviewContainer) {
                            attachPreviewContainer.classList.remove('d-none');
                        }
                    });
                }
            });
        }

        if (btnRemoveAttach) {
            btnRemoveAttach.addEventListener('click', function() {
                resetAttachment();
            });
        }

        if (btnDismissAttachError) {
            btnDismissAttachError.addEventListener('click', function() {
                hideAttachError();
            });
        }

        form.addEventListener('submit', function(e) {
            if (attachCompressingNotice && !attachCompressingNotice.classList.contains('d-none')) {
                e.preventDefault();
                alert('Gambar lampiran sedang dioptimalkan. Mohon tunggu sesaat sebelum mengirim.');
                return;
            }

            try {
                localStorage.removeItem(STORAGE_KEY);
            } catch (err) {
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
