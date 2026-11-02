@extends('layouts.app')

@section('title', 'Master Pengguna - SLKP KPU Bukittinggi')
@section('page-title', 'Master Pengguna & Hak Akses')

@section('content')
<div class="container-fluid">
    <!-- Header (Clean Single Action CTA) -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h5 class="fw-bold text-dark mb-0.5">Master Pengguna &amp; Hak Akses (RBAC)</h5>
            <p class="text-muted small mb-0">Manajemen akun pengguna sistem: Admin, Supervisor, dan Karyawan</p>
        </div>
        <div>
            <button type="button" class="btn btn-kpu btn-sm" data-bs-toggle="modal" data-bs-target="#tambahUserModal">
                <i class="bi bi-person-plus-fill"></i> Tambah Pengguna
            </button>
        </div>
    </div>

    <!-- Search & Filter Card (Compact) -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form action="{{ route('admin.user.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-8">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text"
                               name="search"
                               class="form-control border-start-0"
                               placeholder="Cari berdasarkan nama, username, atau NIP..."
                               value="{{ request('search') }}">
                    </div>
                </div>

                <div class="col-md-2">
                    <select name="role" class="form-select form-select-sm">
                        <option value="">Semua Role</option>
                        <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="supervisor" {{ request('role') == 'supervisor' ? 'selected' : '' }}>Supervisor</option>
                        <option value="karyawan" {{ request('role') == 'karyawan' ? 'selected' : '' }}>Karyawan</option>
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-kpu flex-grow-1">Filter</button>
                    @if(request()->hasAny(['search', 'role']))
                        <a href="{{ route('admin.user.index') }}" class="btn btn-sm btn-light border text-secondary" title="Reset Filter">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- User Data Table Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 50px;" class="text-center">No</th>
                        <th>Nama Pengguna</th>
                        <th>Username</th>
                        <th style="width: 120px;" class="text-center">Role</th>
                        <th>NIP Terhubung</th>
                        <th style="width: 150px;">Tanggal Daftar</th>
                        <th style="width: 120px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $index => $u)
                        <tr>
                            <td class="text-center text-muted small">{{ $users->firstItem() + $index }}</td>
                            <td>
                                <strong class="text-dark">{{ $u->name }}</strong>
                                @if ($u->id === auth()->id())
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 ms-1" style="font-size: 0.68rem;">(Anda)</span>
                                @endif
                            </td>
                            <td><code class="text-secondary small">{{ $u->username }}</code></td>
                            <td class="text-center">
                                <span class="badge {{ $u->role == 'admin' ? 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25' : ($u->role == 'supervisor' ? 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25' : 'bg-success bg-opacity-10 text-success border border-success border-opacity-25') }}" style="font-size: 0.72rem; padding: 4px 8px;">
                                    {{ strtoupper($u->role) }}
                                </span>
                            </td>
                            <td>
                                @if ($u->nip)
                                    <div><code class="small">{{ $u->nip }}</code></div>
                                    <small class="text-muted" style="font-size: 0.75rem;">{{ $u->karyawan?->nama }}</small>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td><small class="text-muted">{{ $u->created_at?->format('d/m/Y H:i') }}</small></td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-1">
                                    <!-- Edit Trigger Modal -->
                                    <button type="button" class="btn btn-sm btn-light border py-1 px-2" data-bs-toggle="modal" data-bs-target="#editUserModal{{ $u->id }}" title="Edit pengguna">
                                        <i class="bi bi-pencil-square text-primary"></i>
                                    </button>

                                    <!-- Delete Form -->
                                    @if ($u->id !== auth()->id())
                                        <form action="{{ route('admin.user.destroy', $u) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun user {{ $u->username }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light border py-1 px-2" title="Hapus pengguna">
                                                <i class="bi bi-trash text-danger"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>

                                <!-- Edit Modal -->
                                <div class="modal fade text-start" id="editUserModal{{ $u->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 shadow" style="border-radius: 14px;">
                                            <form action="{{ route('admin.user.update', $u) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header border-bottom py-3">
                                                    <h6 class="modal-title fw-bold">Edit Akun: {{ $u->username }}</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                                        <input type="text" name="name" class="form-control" value="{{ $u->name }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Username <span class="text-danger">*</span></label>
                                                        <input type="text" name="username" class="form-control" value="{{ $u->username }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Password Baru <span class="text-muted fw-normal">(Kosongkan jika tidak diubah)</span></label>
                                                        <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Role Akses <span class="text-danger">*</span></label>
                                                        <select name="role" class="form-select" required>
                                                            <option value="karyawan" {{ $u->role == 'karyawan' ? 'selected' : '' }}>Karyawan</option>
                                                            <option value="supervisor" {{ $u->role == 'supervisor' ? 'selected' : '' }}>Supervisor</option>
                                                            <option value="admin" {{ $u->role == 'admin' ? 'selected' : '' }}>Admin</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">Tautkan NIP Pegawai</label>
                                                        <select name="nip" class="form-select">
                                                            <option value="">-- Tanpa Tautan NIP --</option>
                                                            @foreach ($karyawans as $k)
                                                                <option value="{{ $k->nip }}" {{ $u->nip == $k->nip ? 'selected' : '' }}>
                                                                    {{ $k->nama }} ({{ $k->nip }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer border-top py-2.5">
                                                    <button type="button" class="btn btn-sm btn-light text-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-sm btn-kpu">Simpan Perubahan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-person-x fs-2 d-block mb-2 text-secondary"></i>
                                Belum ada akun pengguna yang terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="card-footer bg-white border-top py-3 d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Menampilkan {{ $users->firstItem() }} sampai {{ $users->lastItem() }} dari {{ $users->total() }} akun
                </small>
                <div>
                    {{ $users->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Modal Tambah Pengguna Baru -->
<div class="modal fade" id="tambahUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 14px;">
            <form action="{{ route('admin.user.store') }}" method="POST">
                @csrf
                <div class="modal-header border-bottom py-3">
                    <h6 class="modal-title fw-bold">Tambah Akun Pengguna Baru</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text"
                               name="name"
                               class="form-control @error('name') is-invalid @enderror"
                               placeholder="Nama lengkap..."
                               value="{{ old('name') }}"
                               required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text"
                               name="username"
                               class="form-control @error('username') is-invalid @enderror"
                               placeholder="Username unik..."
                               value="{{ old('username') }}"
                               required>
                        @error('username')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password"
                               name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               placeholder="Minimal 6 karakter..."
                               required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Role Akses <span class="text-danger">*</span></label>
                        <select name="role" class="form-select @error('role') is-invalid @enderror" required>
                            <option value="karyawan" {{ old('role') == 'karyawan' ? 'selected' : '' }}>Karyawan</option>
                            <option value="supervisor" {{ old('role') == 'supervisor' ? 'selected' : '' }}>Supervisor</option>
                            <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                        </select>
                        @error('role')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Tautkan NIP Pegawai <span class="text-muted fw-normal">(Opsional)</span></label>
                        <select name="nip" class="form-select @error('nip') is-invalid @enderror">
                            <option value="">-- Tanpa Tautan NIP --</option>
                            @foreach ($karyawans as $k)
                                <option value="{{ $k->nip }}" {{ old('nip') == $k->nip ? 'selected' : '' }}>
                                    {{ $k->nama }} ({{ $k->nip }})
                                </option>
                            @endforeach
                        </select>
                        @error('nip')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer border-top py-2.5">
                    <button type="button" class="btn btn-sm btn-light text-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-kpu">Simpan Pengguna</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
