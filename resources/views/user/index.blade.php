@extends('layouts.app')

@section('title', 'Master Pengguna - SLKP KPU Bukittinggi')
@section('page-title', 'Master Pengguna & Hak Akses')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="mb-1 fw-bold text-dark">Master Pengguna &amp; Hak Akses (RBAC)</h4>
            <p class="text-muted small mb-0">Manajemen akun pengguna sistem: Admin, Supervisor, dan Karyawan</p>
        </div>
    </div>

    <!-- Tambah User Baru Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-person-plus-fill me-1 text-danger"></i> Tambah Akun Pengguna Baru</h6>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('admin.user.store') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-secondary">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text"
                               name="name"
                               class="form-control form-control-sm @error('name') is-invalid @enderror"
                               placeholder="Nama lengkap..."
                               value="{{ old('name') }}"
                               required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-semibold text-secondary">Username <span class="text-danger">*</span></label>
                        <input type="text"
                               name="username"
                               class="form-control form-control-sm @error('username') is-invalid @enderror"
                               placeholder="Username..."
                               value="{{ old('username') }}"
                               required>
                        @error('username')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-semibold text-secondary">Password <span class="text-danger">*</span></label>
                        <input type="password"
                               name="password"
                               class="form-control form-control-sm @error('password') is-invalid @enderror"
                               placeholder="Min. 6 digit..."
                               required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-semibold text-secondary">Role Akses <span class="text-danger">*</span></label>
                        <select name="role" class="form-select form-select-sm @error('role') is-invalid @enderror" required>
                            <option value="karyawan" {{ old('role') == 'karyawan' ? 'selected' : '' }}>Karyawan</option>
                            <option value="supervisor" {{ old('role') == 'supervisor' ? 'selected' : '' }}>Supervisor</option>
                            <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                        </select>
                        @error('role')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-secondary">Tautkan NIP Pegawai (Opsional)</label>
                        <select name="nip" class="form-select form-select-sm @error('nip') is-invalid @enderror">
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

                    <div class="col-12 text-end pt-2 border-top">
                        <button type="submit" class="btn btn-sm btn-danger px-4 fw-semibold">
                            <i class="bi bi-save me-1"></i> Simpan Pengguna Baru
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Search & Filter Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('admin.user.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-8">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text"
                               name="search"
                               class="form-control"
                               placeholder="Cari berdasarkan nama, username, email, atau NIP..."
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
                    <button type="submit" class="btn btn-sm btn-dark w-100">Filter</button>
                    @if(request()->hasAny(['search', 'role']))
                        <a href="{{ route('admin.user.index') }}" class="btn btn-sm btn-outline-secondary" title="Reset Filter">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- User Data Table Card -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover table-striped align-middle mb-0">
                <thead class="table-light text-secondary">
                    <tr>
                        <th style="width: 50px;" class="text-center">No</th>
                        <th>Nama Pengguna</th>
                        <th>Username</th>
                        <th style="width: 120px;" class="text-center">Role</th>
                        <th>NIP Terhubung</th>
                        <th style="width: 150px;">Tanggal Daftar</th>
                        <th style="width: 130px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $index => $u)
                        <tr>
                            <td class="text-center text-muted">{{ $users->firstItem() + $index }}</td>
                            <td>
                                <strong class="text-dark">{{ $u->name }}</strong>
                                @if ($u->id === auth()->id())
                                    <span class="badge bg-secondary-subtle text-secondary ms-1">(Anda)</span>
                                @endif
                            </td>
                            <td><code>{{ $u->username }}</code></td>
                            <td class="text-center">
                                <span class="badge {{ $u->role == 'admin' ? 'bg-danger' : ($u->role == 'supervisor' ? 'bg-primary' : 'bg-success') }}">
                                    {{ ucfirst($u->role) }}
                                </span>
                            </td>
                            <td>
                                @if ($u->nip)
                                    <div><code>{{ $u->nip }}</code></div>
                                    <small class="text-muted">{{ $u->karyawan?->nama }}</small>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td>{{ $u->created_at?->format('d-m-Y H:i') }}</td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-1">
                                    <!-- Edit Trigger Modal -->
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editUserModal{{ $u->id }}" title="Edit pengguna">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <!-- Delete Form -->
                                    @if ($u->id !== auth()->id())
                                        <form action="{{ route('admin.user.destroy', $u) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun user {{ $u->username }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus pengguna">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>

                                <!-- Edit Modal -->
                                <div class="modal fade text-start" id="editUserModal{{ $u->id }}" tabindex="-1" aria-labelledby="modalLabel{{ $u->id }}" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <form action="{{ route('admin.user.update', $u) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header">
                                                    <h6 class="modal-title fw-bold" id="modalLabel{{ $u->id }}">Edit Akun Pengguna: {{ $u->username }}</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Nama Lengkap</label>
                                                        <input type="text" name="name" class="form-control" value="{{ $u->name }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Username</label>
                                                        <input type="text" name="username" class="form-control" value="{{ $u->username }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Password Baru (Kosongkan jika tidak diubah)</label>
                                                        <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Role</label>
                                                        <select name="role" class="form-select" required>
                                                            <option value="karyawan" {{ $u->role == 'karyawan' ? 'selected' : '' }}>Karyawan</option>
                                                            <option value="supervisor" {{ $u->role == 'supervisor' ? 'selected' : '' }}>Supervisor</option>
                                                            <option value="admin" {{ $u->role == 'admin' ? 'selected' : '' }}>Admin</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label small fw-semibold">Tautkan NIP Pegawai</label>
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
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-sm btn-primary">Simpan Perubahan</button>
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
                                <i class="bi bi-people fs-2 d-block mb-2 text-secondary"></i>
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
                    Menampilkan {{ $users->firstItem() }} sampai {{ $users->lastItem() }} dari {{ $users->total() }} pengguna
                </small>
                <div>
                    {{ $users->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
