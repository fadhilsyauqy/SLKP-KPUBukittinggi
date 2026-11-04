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
            <!-- Alert Pemulihan Draf Otomatis (TASK-1.2) -->
            <div id="draftAlertNotice" class="alert alert-primary bg-primary bg-opacity-10 border border-primary border-opacity-25 py-2 px-3 small d-flex align-items-center justify-content-between mb-3 d-none" role="alert">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-clock-history text-primary fs-6"></i>
                    <div>
                        <strong class="text-primary">Draf sebelumnya dipulihkan.</strong>
                        <span id="draftAlertTime" class="text-muted ms-1"></span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" id="btnDismissDraftNotice" class="btn-close" aria-label="Tutup" style="font-size: 0.7rem;"></button>
                </div>
            </div>

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
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="uraian" class="form-label mb-0">Uraian Tugas / Pekerjaan Hari Ini <span class="text-danger">*</span></label>
                                    <button type="button"
                                            id="btnCopyLast"
                                            class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1.5 py-0.5 px-2 rounded-2"
                                            style="font-size: 0.775rem;"
                                            title="Salin uraian dan output dari laporan kehadiran terakhir Anda">
                                        <i class="bi bi-clock-history"></i>
                                        <span id="btnCopyLastText">Gunakan Uraian Kemarin</span>
                                        <span id="btnCopyLastSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true" style="width: 0.75rem; height: 0.75rem;"></span>
                                    </button>
                                </div>
                                <div id="copyFeedback" class="small mt-1 mb-2 d-none"></div>
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

        // Elemen fitur Quick Copy Laporan Terakhir (TASK-1.1)
        var btnCopyLast = document.getElementById('btnCopyLast');
        var btnCopyLastText = document.getElementById('btnCopyLastText');
        var btnCopyLastSpinner = document.getElementById('btnCopyLastSpinner');
        var copyFeedback = document.getElementById('copyFeedback');
        var uraianTextarea = document.getElementById('uraian');
        var outputTextarea = document.getElementById('output');
        var feedbackTimeout = null;

        function showCopyFeedback(type, message) {
            if (feedbackTimeout) {
                clearTimeout(feedbackTimeout);
            }
            var bgClass = 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25';
            var iconClass = 'bi-exclamation-circle-fill';

            if (type === 'success') {
                bgClass = 'bg-success bg-opacity-10 text-success border border-success border-opacity-25';
                iconClass = 'bi-check-circle-fill';
            } else if (type === 'warning') {
                bgClass = 'bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25';
                iconClass = 'bi-info-circle-fill';
            }

            copyFeedback.className = 'small mt-1 mb-2 d-flex align-items-center gap-2 py-1 px-2.5 rounded-2 ' + bgClass;
            copyFeedback.innerHTML = '<i class="bi ' + iconClass + '"></i><span>' + message + '</span>';
            copyFeedback.classList.remove('d-none');

            feedbackTimeout = setTimeout(function() {
                copyFeedback.classList.add('d-none');
            }, 6000);
        }

        if (btnCopyLast) {
            btnCopyLast.addEventListener('click', function() {
                if (uraianTextarea.value.trim() !== '' || outputTextarea.value.trim() !== '') {
                    if (!confirm('Kolom uraian atau output sudah memiliki isian. Apakah Anda ingin menimpanya dengan isi laporan kehadiran kemarin?')) {
                        return;
                    }
                }

                btnCopyLast.disabled = true;
                btnCopyLastSpinner.classList.remove('d-none');
                btnCopyLastText.textContent = 'Memuat...';

                var currentNip = nipInput ? nipInput.value : '';
                var fetchUrl = "{{ route('laporan.hadir.last') }}" + (currentNip ? '?nip=' + encodeURIComponent(currentNip) : '');

                fetch(fetchUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(function(res) {
                    if (!res.ok) {
                        throw new Error('HTTP ' + res.status);
                    }
                    return res.json();
                })
                .then(function(data) {
                    if (data.status === 'success' && data.data) {
                        uraianTextarea.value = data.data.uraian || '';
                        outputTextarea.value = data.data.output || '';

                        uraianTextarea.dispatchEvent(new Event('input', { bubbles: true }));
                        outputTextarea.dispatchEvent(new Event('input', { bubbles: true }));

                        showCopyFeedback('success', 'Berhasil menyalin uraian dari laporan tanggal ' + (data.data.tanggal || 'kemarin') + '.');
                    } else if (data.status === 'empty') {
                        showCopyFeedback('warning', data.message || 'Belum ada riwayat laporan kehadiran sebelumnya.');
                    } else {
                        showCopyFeedback('danger', data.message || 'Gagal mengambil riwayat laporan terakhir.');
                    }
                })
                .catch(function(err) {
                    console.error('Quick copy error:', err);
                    showCopyFeedback('danger', 'Terjadi gangguan saat mengambil data laporan. Silakan coba kembali.');
                })
                .finally(function() {
                    btnCopyLast.disabled = false;
                    btnCopyLastSpinner.classList.add('d-none');
                    btnCopyLastText.textContent = 'Gunakan Uraian Kemarin';
                });
            });
        }

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
        var STORAGE_KEY = 'slkp_draft_hadir_{{ auth()->id() ?? 'guest' }}';
        var tanggalInput = document.getElementById('tanggal');
        var jamMasukInput = document.getElementById('jam_masuk');
        var jamKeluarInput = document.getElementById('jam_keluar');
        var draftAlertNotice = document.getElementById('draftAlertNotice');
        var draftAlertTime = document.getElementById('draftAlertTime');
        var btnDismissDraftNotice = document.getElementById('btnDismissDraftNotice');
        var btnClearDraftBtn = document.getElementById('btnClearDraftBtn');
        var draftAutoSaveStatus = document.getElementById('draftAutoSaveStatus');
        var saveDraftTimeout = null;

        function saveDraft() {
            try {
                var uraianVal = uraianTextarea ? uraianTextarea.value.trim() : '';
                var outputVal = outputTextarea ? outputTextarea.value.trim() : '';

                if (uraianVal !== '' || outputVal !== '') {
                    var now = new Date();
                    var timeFormatted = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                    var draftData = {
                        nama: namaSelect ? namaSelect.value : '',
                        tanggal: tanggalInput ? tanggalInput.value : '',
                        jam_masuk: jamMasukInput ? jamMasukInput.value : '',
                        jam_keluar: jamKeluarInput ? jamKeluarInput.value : '',
                        uraian: uraianTextarea ? uraianTextarea.value : '',
                        output: outputTextarea ? outputTextarea.value : '',
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

                var isUraianEmpty = !uraianTextarea || uraianTextarea.value.trim() === '';
                var isOutputEmpty = !outputTextarea || outputTextarea.value.trim() === '';

                if (isUraianEmpty && isOutputEmpty && (draft.uraian || draft.output)) {
                    if (draft.uraian && uraianTextarea) {
                        uraianTextarea.value = draft.uraian;
                    }
                    if (draft.output && outputTextarea) {
                        outputTextarea.value = draft.output;
                    }
                    if (draft.tanggal && tanggalInput) {
                        tanggalInput.value = draft.tanggal;
                    }
                    if (draft.jam_masuk && jamMasukInput) {
                        jamMasukInput.value = draft.jam_masuk;
                    }
                    if (draft.jam_keluar && jamKeluarInput) {
                        jamKeluarInput.value = draft.jam_keluar;
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
                        draftAutoSaveStatus.innerHTML = '<i class="bi bi-clock-history text-primary"></i> Draf dipulihkan';
                        draftAutoSaveStatus.classList.remove('d-none');
                    }
                } else if (!isUraianEmpty || !isOutputEmpty) {
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
                if (!confirm('Apakah Anda yakin ingin menghapus draf isian formulir ini? Semua teks yang belum dikirim akan dikosongkan.')) {
                    return;
                }
            }

            try {
                localStorage.removeItem(STORAGE_KEY);
            } catch (e) {
                // ignore
            }

            if (uraianTextarea) uraianTextarea.value = '';
            if (outputTextarea) outputTextarea.value = '';
            if (jamMasukInput) jamMasukInput.value = '07:30';
            if (jamKeluarInput) jamKeluarInput.value = '16:00';
            if (tanggalInput) {
                var today = new Date().toISOString().split('T')[0];
                tanggalInput.value = today;
            }

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

        if (uraianTextarea) uraianTextarea.addEventListener('input', triggerDebouncedAutoSave);
        if (outputTextarea) outputTextarea.addEventListener('input', triggerDebouncedAutoSave);
        if (tanggalInput) tanggalInput.addEventListener('change', triggerDebouncedAutoSave);
        if (jamMasukInput) jamMasukInput.addEventListener('change', triggerDebouncedAutoSave);
        if (jamKeluarInput) jamKeluarInput.addEventListener('change', triggerDebouncedAutoSave);
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
