@extends('layouts.app')

@section('title', 'Master Data Pegawai - SLKP KPU Bukittinggi')
@section('page-title', 'Master Data Pegawai')

@section('content')
<div class="container-fluid">
    <!-- Header (Clean Single Action CTA) -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h5 class="fw-bold text-dark mb-0.5">Master Data Karyawan</h5>
            <p class="text-muted small mb-0">Daftar seluruh pegawai sekretariat KPU Kota Bukittinggi (Terurut Hierarki Jabatan)</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.karyawan.template') }}" class="btn btn-outline-success btn-sm" title="Unduh format template Excel untuk impor massal">
                <i class="bi bi-file-earmark-arrow-down"></i> Unduh Template Excel
            </a>
            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalImportKaryawan">
                <i class="bi bi-file-earmark-arrow-up"></i> Impor Pegawai
            </button>
            <a href="{{ route('admin.karyawan.create') }}" class="btn btn-kpu btn-sm">
                <i class="bi bi-person-plus-fill"></i> Tambah Pegawai
            </a>
        </div>
    </div>

    @if(session('import_result'))
        @php $res = session('import_result'); @endphp
        <div class="card border-0 shadow-sm mb-4 border-start border-4 {{ ($res['imported_count'] ?? 0) > 0 ? 'border-success' : 'border-warning' }}">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <h6 class="fw-bold mb-0 {{ ($res['imported_count'] ?? 0) > 0 ? 'text-success' : 'text-warning' }}">
                        <i class="bi {{ ($res['imported_count'] ?? 0) > 0 ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' }} me-1"></i>
                        Ringkasan Hasil Impor Data Pegawai
                    </h6>
                    <span class="badge bg-light text-secondary border">Total Diproses: {{ $res['total_rows'] ?? 0 }} baris</span>
                </div>
                <div class="d-flex flex-wrap gap-2 mb-2">
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                        <i class="bi bi-check-lg me-1"></i> {{ $res['imported_count'] ?? 0 }} Berhasil Ditambahkan
                    </span>
                    @if(!empty($res['created_jabatans_count']) && $res['created_jabatans_count'] > 0)
                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2 py-1">
                            <i class="bi bi-briefcase me-1"></i> {{ $res['created_jabatans_count'] }} Jabatan Baru Dibuat
                        </span>
                    @endif
                    @if(($res['skipped_count'] ?? 0) > 0)
                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1">
                            <i class="bi bi-exclamation-circle me-1"></i> {{ $res['skipped_count'] }} Dilewati / Tidak Valid
                        </span>
                    @endif
                </div>

                @if(!empty($res['skipped_details']))
                    <div class="mt-2">
                        <a class="btn btn-link btn-sm p-0 text-decoration-none text-muted" data-bs-toggle="collapse" href="#collapseSkippedDetails" role="button" aria-expanded="false" aria-controls="collapseSkippedDetails">
                            <i class="bi bi-chevron-down me-1"></i> Lihat Rincian Baris yang Dilewati ({{ count($res['skipped_details']) }} baris)
                        </a>
                        <div class="collapse mt-2" id="collapseSkippedDetails">
                            <div class="table-responsive border rounded bg-light p-2" style="max-height: 220px; overflow-y: auto;">
                                <table class="table table-sm table-borderless mb-0 small">
                                    <thead>
                                        <tr class="text-muted border-bottom">
                                            <th style="width: 70px;">Baris</th>
                                            <th style="width: 180px;">NIP</th>
                                            <th>Nama Pegawai</th>
                                            <th>Keterangan / Alasan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($res['skipped_details'] as $detail)
                                            <tr>
                                                <td><span class="badge bg-secondary">#{{ $detail['row'] }}</span></td>
                                                <td><code>{{ $detail['nip'] }}</code></td>
                                                <td class="text-dark">{{ $detail['nama'] }}</td>
                                                <td class="text-danger fw-medium">{{ $detail['reason'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Search & Filter Card (Compact) -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form action="{{ route('admin.karyawan.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text"
                               name="search"
                               class="form-control border-start-0"
                               placeholder="Cari berdasarkan NIP, nama lengkap, atau jabatan..."
                               value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="sub_bagian" class="form-select form-select-sm">
                        <option value="">Semua Sub Bagian / Divisi</option>
                        @foreach ($subBagians as $key => $label)
                            <option value="{{ $key }}" {{ request('sub_bagian') === $key ? 'selected' : '' }}>
                                {{ $key }} ({{ $label }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-kpu flex-grow-1">Filter</button>
                    @if(request()->hasAny(['search', 'sub_bagian']))
                        <a href="{{ route('admin.karyawan.index') }}" class="btn btn-sm btn-light border text-secondary" title="Reset Filter">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 50px;" class="text-center">No</th>
                        <th style="width: 210px;" class="text-nowrap">NIP</th>
                        <th>Nama Pegawai</th>
                        <th>Jabatan Terkait</th>
                        <th style="width: 170px;">Sub Bagian</th>
                        <th style="width: 130px;" class="text-center">Hierarki</th>
                        <th style="width: 130px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($karyawans as $index => $k)
                        <tr>
                            <td class="text-center text-muted small">{{ $karyawans->firstItem() + $index }}</td>
                            <td><code class="text-secondary small text-nowrap">{{ $k->nip }}</code></td>
                            <td>
                                <div class="fw-bold text-dark">{{ $k->nama }}</div>
                            </td>
                            <td>
                                @if ($k->jabatan)
                                    <span class="badge bg-light text-secondary border fw-normal" style="font-size: 0.75rem;">{{ $k->jabatan->nama_jabatan }}</span>
                                @else
                                    <span class="text-muted small"><em>Belum ada jabatan</em></span>
                                @endif
                            </td>
                            <td>
                                @if ($k->effective_sub_bagian)
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25" style="font-size: 0.72rem; padding: 4px 8px;">
                                        <i class="bi bi-diagram-2 me-1"></i> {{ $k->effective_sub_bagian }}
                                    </span>
                                @else
                                    <span class="text-muted small fst-italic">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $k->sort_order <= 2 ? 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25' : ($k->sort_order <= 5 ? 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25' : 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25') }}" style="font-size: 0.72rem; padding: 4px 8px;">
                                    Urutan ke-{{ $k->sort_order }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('admin.karyawan.edit', $k) }}" class="btn btn-sm btn-light border py-1 px-2" title="Ubah data pegawai">
                                        <i class="bi bi-pencil-square text-primary"></i>
                                    </a>
                                    <form action="{{ route('admin.karyawan.destroy', $k) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data pegawai {{ $k->nama }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light border py-1 px-2" title="Hapus pegawai">
                                            <i class="bi bi-trash text-danger"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-people fs-2 d-block mb-2 text-secondary"></i>
                                Belum ada data pegawai yang terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($karyawans->hasPages())
            <div class="card-footer bg-white border-top py-3 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <small class="text-muted fw-medium">
                    Menampilkan {{ $karyawans->firstItem() }} - {{ $karyawans->lastItem() }} dari total {{ $karyawans->total() }} pegawai
                </small>
                <div>
                    {{ $karyawans->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- Modal Impor Data Pegawai Massal -->
    <div class="modal fade" id="modalImportKaryawan" tabindex="-1" aria-labelledby="modalImportKaryawanLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-kpu text-white py-3">
                    <h6 class="modal-title fw-bold" id="modalImportKaryawanLabel">
                        <i class="bi bi-file-earmark-spreadsheet me-2"></i> Impor Data Pegawai Massal
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('admin.karyawan.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="alert alert-light border small text-secondary mb-3">
                            <div class="fw-bold text-dark mb-1"><i class="bi bi-info-circle me-1 text-primary"></i> Ketentuan Pengisian File:</div>
                            <ul class="ps-3 mb-0">
                                <li>Mendukung format <strong>.xlsx, .xls</strong> atau <strong>.csv</strong> (Maksimal 5 MB).</li>
                                <li>Format kolom: <code>No</code>, <code>NIP</code>, <code>Nama Lengkap</code>, <code>Jabatan</code>, <code>Sub Bagian</code>.</li>
                                <li><strong>Validasi NIP:</strong> NIP harus unik. Jika sudah terdaftar atau duplikat dalam file, baris akan otomatis dilewati.</li>
                                <li>Master jabatan baru akan <strong>otomatis dibuat</strong> jika belum tersedia di sistem.</li>
                            </ul>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded border">
                            <span class="small text-muted">Gunakan template resmi KPU:</span>
                            <a href="{{ route('admin.karyawan.template') }}" class="btn btn-sm btn-outline-success">
                                <i class="bi bi-download me-1"></i> Unduh Template (.xlsx)
                            </a>
                        </div>

                        <div class="mb-2">
                            <label for="importFile" class="form-label fw-semibold small text-dark">Pilih File Excel / CSV <span class="text-danger">*</span></label>
                            <input type="file"
                                   name="file"
                                   id="importFile"
                                   class="form-control form-control-sm"
                                   accept=".xlsx,.xls,.csv,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel"
                                   required>
                            <div class="form-text small text-muted">Pastikan file sudah ditutup di aplikasi Excel sebelum diunggah.</div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2 px-4">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-sm btn-kpu">
                            <i class="bi bi-cloud-arrow-up-fill me-1"></i> Mulai Impor Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
