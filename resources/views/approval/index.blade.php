@extends('layouts.app')

@section('title', 'Persetujuan Izin & Cuti - SLKP KPU Bukittinggi')
@section('page-title', 'Verifikasi & Persetujuan Ketidakhadiran')

@section('content')
<div class="container-fluid">
    <!-- Header (Title & Summary) -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h5 class="fw-bold text-dark mb-0.5">Persetujuan Izin, Cuti &amp; Dinas Pegawai</h5>
            <p class="text-muted small mb-0">Verifikasi pengajuan ketidakhadiran kerja pegawai Sekretariat KPU Kota Bukittinggi</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('monitoring.index') }}" class="btn btn-outline-secondary btn-sm shadow-sm">
                <i class="bi bi-speedometer2 me-1"></i> Kembali ke Monitoring
            </a>
        </div>
    </div>

    <!-- Status Tabs -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-2">
            <ul class="nav nav-pills nav-fill gap-2" style="font-size: 0.85rem;">
                <li class="nav-item">
                    <a class="nav-link py-2 {{ $statusFilter === 'pending' ? 'active bg-danger' : 'text-secondary' }}" 
                       href="{{ route('approval.index', array_merge(request()->except('page', 'status'), ['status' => 'pending'])) }}">
                        <i class="bi bi-hourglass-split me-1"></i> Menunggu Verifikasi
                        <span class="badge {{ $statusFilter === 'pending' ? 'bg-white text-danger' : 'bg-danger text-white' }} ms-1">
                            {{ $counts['pending'] }}
                        </span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-2 {{ $statusFilter === 'approved' ? 'active bg-success' : 'text-secondary' }}" 
                       href="{{ route('approval.index', array_merge(request()->except('page', 'status'), ['status' => 'approved'])) }}">
                        <i class="bi bi-check-circle-fill me-1"></i> Telah Disetujui
                        <span class="badge {{ $statusFilter === 'approved' ? 'bg-white text-success' : 'bg-success text-white' }} ms-1">
                            {{ $counts['approved'] }}
                        </span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-2 {{ $statusFilter === 'rejected' ? 'active bg-secondary' : 'text-secondary' }}" 
                       href="{{ route('approval.index', array_merge(request()->except('page', 'status'), ['status' => 'rejected'])) }}">
                        <i class="bi bi-x-circle-fill me-1"></i> Ditolak
                        <span class="badge {{ $statusFilter === 'rejected' ? 'bg-white text-secondary' : 'bg-secondary text-white' }} ms-1">
                            {{ $counts['rejected'] }}
                        </span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-2 {{ empty($statusFilter) ? 'active bg-dark' : 'text-secondary' }}" 
                       href="{{ route('approval.index', array_merge(request()->except('page', 'status'), ['status' => ''])) }}">
                        <i class="bi bi-collection-fill me-1"></i> Semua Pengajuan
                        <span class="badge {{ empty($statusFilter) ? 'bg-white text-dark' : 'bg-dark text-white' }} ms-1">
                            {{ $counts['total'] }}
                        </span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form action="{{ route('approval.index') }}" method="GET" class="row g-2 align-items-end">
                <input type="hidden" name="status" value="{{ $statusFilter }}">

                <div class="col-md-5">
                    <label class="form-label small text-muted">Cari Nama Pegawai / NIP / Alasan</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text"
                               name="search"
                               class="form-control border-start-0"
                               placeholder="Ketik kata kunci..."
                               value="{{ request('search') }}">
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label small text-muted">Dari Tanggal</label>
                    <input type="date"
                           name="tanggal_mulai"
                           class="form-control form-control-sm"
                           value="{{ request('tanggal_mulai') }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label small text-muted">Sampai Tanggal</label>
                    <input type="date"
                           name="tanggal_selesai"
                           class="form-control form-control-sm"
                           value="{{ request('tanggal_selesai') }}">
                </div>

                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-kpu flex-grow-1" title="Terapkan Filter">
                        <i class="bi bi-funnel-fill"></i>
                    </button>
                    @if(request()->hasAny(['search', 'tanggal_mulai', 'tanggal_selesai']))
                        <a href="{{ route('approval.index', ['status' => $statusFilter]) }}" class="btn btn-sm btn-light border text-secondary" title="Reset Filter">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Table of Absence Requests -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;" class="text-center">
                            <input type="checkbox" id="selectAllCheckbox" class="form-check-input" title="Pilih Semua yang Pending">
                        </th>
                        <th style="width: 45px;" class="text-center">No</th>
                        <th style="width: 220px;">Pegawai</th>
                        <th style="width: 140px;">Jenis Izin</th>
                        <th style="width: 160px;">Tanggal &amp; Durasi</th>
                        <th>Alasan &amp; Bukti</th>
                        <th style="width: 150px;" class="text-center">Status</th>
                        <th style="width: 170px;" class="text-center">Aksi / Verifikasi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($laporans as $index => $row)
                        <tr>
                            <td class="text-center">
                                @if($row->status_approval === 'pending')
                                    <input type="checkbox"
                                           class="form-check-input bulk-checkbox"
                                           value="{{ $row->id }}"
                                           data-nama="{{ $row->nama }}"
                                           data-keterangan="{{ $row->keterangan ?? 'Izin' }}"
                                           aria-label="Pilih permohonan {{ $row->nama }}">
                                @else
                                    <input type="checkbox" class="form-check-input" disabled aria-hidden="true" style="opacity: 0.35;">
                                @endif
                            </td>
                            <td class="text-center text-muted small">{{ $laporans->firstItem() + $index }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $row->nama }}</div>
                                <div class="small text-muted font-monospace">{{ $row->nip ?? '-' }}</div>
                                <span class="badge bg-light text-secondary border fw-normal mt-1" style="font-size: 0.7rem;">
                                    {{ $row->jabatan ?? '-' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25 px-2.5 py-1.5" style="font-size: 0.78rem;">
                                    <i class="bi bi-calendar-event me-1"></i> {{ $row->keterangan ?? 'Izin' }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark small">
                                    {{ $row->tanggal?->format('d/m/Y') }}
                                    @if($row->tanggal_selesai && $row->tanggal_selesai != $row->tanggal)
                                        <span class="text-muted d-block font-normal" style="font-size: 0.75rem;">s/d {{ $row->tanggal_selesai->format('d/m/Y') }}</span>
                                    @endif
                                </div>
                                <small class="text-secondary d-block mt-0.5"><i class="bi bi-hourglass-split me-1"></i> {{ $row->lama_hari ?? '1 Hari' }}</small>
                            </td>
                            <td>
                                <div class="text-dark small mb-1">{{ $row->alasan ?? '-' }}</div>
                                @if($row->bukti)
                                    <a href="{{ asset('storage/' . $row->bukti) }}" target="_blank" class="btn btn-sm btn-light border py-0.5 px-2 text-danger" style="font-size: 0.75rem;" title="Lihat Lampiran Dokumen">
                                        <i class="bi bi-paperclip me-1"></i> Lampiran Berkas
                                    </a>
                                @else
                                    <small class="text-muted fst-italic" style="font-size: 0.72rem;">Tidak melampirkan berkas</small>
                                @endif

                                @if($row->catatan_approval)
                                    <div class="mt-2 p-1.5 px-2 bg-light rounded border text-muted small" style="font-size: 0.75rem;">
                                        <i class="bi bi-chat-left-text me-1 text-secondary"></i>
                                        <strong>Catatan:</strong> {{ $row->catatan_approval }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($row->status_approval === 'approved')
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-1 px-2" style="font-size: 0.75rem;">
                                        <i class="bi bi-check-circle-fill me-1"></i> Disetujui
                                    </span>
                                    @if($row->approver)
                                        <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">
                                            Oleh: {{ $row->approver->name }}
                                        </small>
                                    @endif
                                    @if($row->approved_at)
                                        <small class="text-muted d-block" style="font-size: 0.68rem;">
                                            {{ $row->approved_at->format('d/m/Y H:i') }}
                                        </small>
                                    @endif
                                @elseif($row->status_approval === 'rejected')
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 py-1 px-2" style="font-size: 0.75rem;">
                                        <i class="bi bi-x-circle-fill me-1"></i> Ditolak
                                    </span>
                                    @if($row->approver)
                                        <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">
                                            Oleh: {{ $row->approver->name }}
                                        </small>
                                    @endif
                                    @if($row->approved_at)
                                        <small class="text-muted d-block" style="font-size: 0.68rem;">
                                            {{ $row->approved_at->format('d/m/Y H:i') }}
                                        </small>
                                    @endif
                                @else
                                    <span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25 py-1 px-2" style="font-size: 0.75rem;">
                                        <i class="bi bi-hourglass-split me-1"></i> Menunggu
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($row->status_approval === 'pending')
                                    <div class="d-flex justify-content-center gap-1">
                                        <!-- Tombol Setujui -->
                                        <form action="{{ route('approval.approve', $row) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menyetujui pengajuan {{ $row->keterangan }} a.n. {{ $row->nama }}?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success px-2 py-1 shadow-sm" style="font-size: 0.75rem;" title="Setujui Pengajuan">
                                                <i class="bi bi-check-lg me-1"></i> Setujui
                                            </button>
                                        </form>

                                        <!-- Tombol Tolak (Memicu Modal) -->
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger px-2 py-1 shadow-sm" 
                                                style="font-size: 0.75rem;" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalReject{{ $row->id }}"
                                                title="Tolak Pengajuan">
                                            <i class="bi bi-x-lg me-1"></i> Tolak
                                        </button>
                                    </div>

                                    <!-- Modal Tolak Pengajuan -->
                                    <div class="modal fade modal-reject" id="modalReject{{ $row->id }}" tabindex="-1" aria-labelledby="modalRejectLabel{{ $row->id }}" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered text-start">
                                            <div class="modal-content border-0 shadow">
                                                <div class="modal-header bg-danger text-white">
                                                    <h6 class="modal-title fw-bold" id="modalRejectLabel{{ $row->id }}">
                                                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Tolak Pengajuan Izin / Cuti
                                                    </h6>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="{{ route('approval.reject', $row) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-body p-4">
                                                        <div class="mb-3">
                                                            <div class="fw-semibold text-dark">{{ $row->nama }} ({{ $row->nip ?? '-' }})</div>
                                                            <small class="text-muted">{{ $row->keterangan }} &bull; {{ $row->tanggal?->format('d/m/Y') }} ({{ $row->lama_hari ?? '1 Hari' }})</small>
                                                        </div>

                                                        <!-- Template Alasan Cepat Penolakan (TASK-2.3) -->
                                                        <div class="mb-3">
                                                            <label for="rejectTemplate{{ $row->id }}" class="form-label small fw-semibold text-dark mb-1 d-flex align-items-center gap-1">
                                                                <i class="bi bi-lightning-charge-fill text-warning"></i>
                                                                <span>Pilih Template Alasan Cepat</span>
                                                                <span class="badge bg-light text-secondary border ms-auto" style="font-size: 0.65rem;">Opsional</span>
                                                            </label>
                                                            <select id="rejectTemplate{{ $row->id }}" 
                                                                    class="form-select form-select-sm reject-template-select" 
                                                                    data-target="#catatan_approval_{{ $row->id }}"
                                                                    aria-label="Pilih Template Alasan Penolakan">
                                                                <option value="" selected disabled>-- Pilih template alasan penolakan --</option>
                                                                <option value="Lampiran surat/bukti pendukung tidak jelas atau belum diunggah.">Lampiran surat/bukti pendukung tidak jelas atau belum diunggah</option>
                                                                <option value="Harap sesuaikan tanggal dengan jadwal agenda dinas.">Harap sesuaikan tanggal dengan jadwal agenda dinas</option>
                                                                <option value="Kuota cuti pada periode ini sudah penuh.">Kuota cuti pada periode ini sudah penuh</option>
                                                                <option value="__custom__">Lainnya (Tulis manual)</option>
                                                            </select>
                                                            <div class="form-text text-muted" style="font-size: 0.72rem;">
                                                                Pilih opsi di atas untuk pengisian instan, Anda tetap dapat menyesuaikan teks di bawah.
                                                            </div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="catatan_approval_{{ $row->id }}" class="form-label small fw-semibold text-dark">Alasan Penolakan <span class="text-danger">*</span></label>
                                                            <textarea name="catatan_approval" 
                                                                      id="catatan_approval_{{ $row->id }}"
                                                                      class="form-control" 
                                                                      rows="3" 
                                                                      required 
                                                                      minlength="3" 
                                                                      maxlength="500" 
                                                                      placeholder="Tuliskan alasan penolakan secara jelas agar pegawai memahami..."></textarea>
                                                            <small class="text-muted" style="font-size: 0.72rem;">Wajib diisi sebagai catatan resmi penolakan dinas.</small>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer bg-light">
                                                        <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-sm btn-danger px-3">
                                                            <i class="bi bi-x-circle me-1"></i> Konfirmasi Tolak
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    @if ($row->status_approval === 'approved')
                                        <div class="d-flex flex-column align-items-center gap-1">
                                            <span class="text-success small fw-medium">
                                                <i class="bi bi-check2-all"></i> Selesai
                                            </span>
                                            <a href="{{ route('laporan.pdf', $row) }}" target="_blank" class="btn btn-outline-danger btn-sm py-0.5 px-2" style="font-size: 0.72rem;" title="Lihat Dokumen PDF Resmi">
                                                <i class="bi bi-file-earmark-pdf me-0.5"></i> PDF
                                            </a>
                                        </div>
                                    @else
                                        <span class="text-muted small">
                                            <i class="bi bi-x-circle text-danger"></i> Ditolak
                                        </span>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                <div>Tidak ada permohonan izin/cuti dalam antrean ini.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($laporans->hasPages())
            <div class="card-footer bg-white border-top py-3 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <small class="text-muted fw-medium">
                    Menampilkan {{ $laporans->firstItem() }} - {{ $laporans->lastItem() }} dari total {{ $laporans->total() }} data
                </small>
                <div>
                    {{ $laporans->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- Floating Bulk Action Toolbar (TASK-2.1) -->
    <div id="bulkActionBar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 shadow-lg p-2.5 px-3 bg-white border border-2 border-danger border-opacity-25 rounded-pill d-none" style="z-index: 1050; min-width: 330px; max-width: 92vw;">
        <div class="d-flex align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-danger rounded-pill px-2.5 py-1.5" style="font-size: 0.85rem;" id="bulkSelectedCount">0</span>
                <span class="small fw-semibold text-dark">Permohonan dipilih</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-1" id="btnCancelBulk" style="font-size: 0.8rem;">
                    Batal
                </button>
                <button type="button" class="btn btn-success btn-sm rounded-pill px-3 py-1 fw-semibold shadow-sm" id="btnTriggerBulkApprove" data-bs-toggle="modal" data-bs-target="#modalBulkApprove" style="font-size: 0.8rem;">
                    <i class="bi bi-check-all me-1"></i> Setujui Terpilih
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Persetujuan Massal (TASK-2.1) -->
    <div class="modal fade" id="modalBulkApprove" tabindex="-1" aria-labelledby="modalBulkApproveLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white">
                    <h6 class="modal-title fw-bold" id="modalBulkApproveLabel">
                        <i class="bi bi-check-circle-fill me-1.5"></i> Konfirmasi Persetujuan Massal
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <form id="bulkApproveForm" action="{{ route('approval.bulk-approve') }}" method="POST">
                    @csrf
                    <div id="bulkApproveHiddenInputs"></div>
                    <div class="modal-body p-4">
                        <p class="text-dark mb-2">
                            Anda akan menyetujui sekaligus <strong id="bulkModalCount" class="text-success">0</strong> permohonan izin/cuti/sakit yang telah dipilih:
                        </p>
                        <div id="bulkModalList" class="p-2.5 mb-3 bg-light rounded border small overflow-auto text-dark" style="max-height: 140px;">
                            <!-- List diisi oleh JavaScript -->
                        </div>
                        <div class="mb-3">
                            <label for="bulk_catatan_approval" class="form-label small fw-semibold text-dark">
                                Catatan Persetujuan Massal <span class="text-muted fw-normal">(Opsional)</span>
                            </label>
                            <input type="text"
                                   name="catatan_approval"
                                   id="bulk_catatan_approval"
                                   class="form-control form-control-sm"
                                   placeholder="Contoh: Disetujui secara massal untuk penugasan tim."
                                   maxlength="500">
                            <div class="form-text text-muted" style="font-size: 0.72rem;">Catatan ini akan tersimpan pada setiap permohonan yang disetujui.</div>
                        </div>
                        <div class="alert alert-info py-2 px-3 small mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-info-circle-fill text-info flex-shrink-0"></i>
                            <span>Permohonan yang disetujui akan otomatis disinkronkan ke Google Sheets dan dicatat pada audit trail log.</span>
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2 px-3">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" id="btnConfirmBulkApprove" class="btn btn-sm btn-success px-3 fw-semibold">
                            <span id="bulkSubmitText"><i class="bi bi-check-lg me-1"></i> Ya, Setujui Semua</span>
                            <span id="bulkSubmitLoading" class="d-none">
                                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                Memproses...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var selectAllCheckbox = document.getElementById('selectAllCheckbox');
        var bulkCheckboxes = document.querySelectorAll('.bulk-checkbox');
        var bulkActionBar = document.getElementById('bulkActionBar');
        var bulkSelectedCount = document.getElementById('bulkSelectedCount');
        var btnCancelBulk = document.getElementById('btnCancelBulk');
        var bulkModalCount = document.getElementById('bulkModalCount');
        var bulkModalList = document.getElementById('bulkModalList');
        var bulkApproveHiddenInputs = document.getElementById('bulkApproveHiddenInputs');
        var bulkApproveForm = document.getElementById('bulkApproveForm');
        var btnConfirmBulkApprove = document.getElementById('btnConfirmBulkApprove');
        var bulkSubmitText = document.getElementById('bulkSubmitText');
        var bulkSubmitLoading = document.getElementById('bulkSubmitLoading');
        var modalBulkApprove = document.getElementById('modalBulkApprove');

        function updateBulkSelection() {
            var checkedItems = document.querySelectorAll('.bulk-checkbox:checked');
            var totalPending = bulkCheckboxes.length;
            var count = checkedItems.length;

            if (bulkSelectedCount) {
                bulkSelectedCount.textContent = count;
            }

            if (count > 0) {
                if (bulkActionBar) {
                    bulkActionBar.classList.remove('d-none');
                }
            } else {
                if (bulkActionBar) {
                    bulkActionBar.classList.add('d-none');
                }
            }

            if (selectAllCheckbox) {
                if (totalPending === 0) {
                    selectAllCheckbox.disabled = true;
                    selectAllCheckbox.checked = false;
                    selectAllCheckbox.indeterminate = false;
                } else if (count === totalPending) {
                    selectAllCheckbox.checked = true;
                    selectAllCheckbox.indeterminate = false;
                } else if (count > 0 && count < totalPending) {
                    selectAllCheckbox.checked = false;
                    selectAllCheckbox.indeterminate = true;
                } else {
                    selectAllCheckbox.checked = false;
                    selectAllCheckbox.indeterminate = false;
                }
            }
        }

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                var isChecked = this.checked;
                bulkCheckboxes.forEach(function(cb) {
                    cb.checked = isChecked;
                });
                updateBulkSelection();
            });
        }

        bulkCheckboxes.forEach(function(cb) {
            cb.addEventListener('change', function() {
                updateBulkSelection();
            });
        });

        if (btnCancelBulk) {
            btnCancelBulk.addEventListener('click', function() {
                bulkCheckboxes.forEach(function(cb) {
                    cb.checked = false;
                });
                updateBulkSelection();
            });
        }

        if (modalBulkApprove) {
            modalBulkApprove.addEventListener('show.bs.modal', function() {
                var checkedItems = document.querySelectorAll('.bulk-checkbox:checked');
                if (bulkModalCount) {
                    bulkModalCount.textContent = checkedItems.length;
                }

                if (bulkApproveHiddenInputs) {
                    bulkApproveHiddenInputs.innerHTML = '';
                    checkedItems.forEach(function(cb) {
                        var hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = 'ids[]';
                        hidden.value = cb.value;
                        bulkApproveHiddenInputs.appendChild(hidden);
                    });
                }

                if (bulkModalList) {
                    bulkModalList.innerHTML = '';
                    if (checkedItems.length === 0) {
                        bulkModalList.innerHTML = '<span class="text-danger">Belum ada permohonan yang dipilih.</span>';
                    } else {
                        var ul = document.createElement('ul');
                        ul.className = 'mb-0 ps-3';
                        checkedItems.forEach(function(cb) {
                            var li = document.createElement('li');
                            var nama = cb.getAttribute('data-nama') || 'Pegawai';
                            var ket = cb.getAttribute('data-keterangan') || 'Izin';
                            li.textContent = nama + ' (' + ket + ')';
                            ul.appendChild(li);
                        });
                        bulkModalList.appendChild(ul);
                    }
                }
            });
        }

        if (bulkApproveForm) {
            bulkApproveForm.addEventListener('submit', function(e) {
                var checkedCount = document.querySelectorAll('.bulk-checkbox:checked').length;
                if (checkedCount === 0) {
                    e.preventDefault();
                    alert('Pilih minimal satu permohonan izin untuk disetujui.');
                    return;
                }

                if (btnConfirmBulkApprove) {
                    btnConfirmBulkApprove.disabled = true;
                }
                if (bulkSubmitText) {
                    bulkSubmitText.classList.add('d-none');
                }
                if (bulkSubmitLoading) {
                    bulkSubmitLoading.classList.remove('d-none');
                }
            });
        }

        // Template Catatan Cepat Penolakan Izin (TASK-2.3)
        document.querySelectorAll('.reject-template-select').forEach(function(select) {
            select.addEventListener('change', function() {
                var targetSelector = this.getAttribute('data-target');
                if (!targetSelector) return;
                var textarea = document.querySelector(targetSelector);
                if (!textarea) return;

                if (this.value === '__custom__') {
                    textarea.value = '';
                    textarea.focus();
                } else if (this.value) {
                    textarea.value = this.value;
                    textarea.focus();
                }
            });
        });

        // Reset form tolak saat modal ditutup
        document.querySelectorAll('.modal-reject').forEach(function(modal) {
            modal.addEventListener('hidden.bs.modal', function() {
                var form = this.querySelector('form');
                if (form) {
                    form.reset();
                }
            });
        });

        updateBulkSelection();
    });
</script>
@endpush
