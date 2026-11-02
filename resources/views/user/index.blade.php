<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Master Pengguna & Role - SLKP KPU Bukittinggi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="{{ asset('assets/img/logo-kpu.png') }}">
</head>
<body class="bg-light p-4">
    <div class="container-fluid" style="max-width: 1100px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-0">Master Pengguna & Hak Akses (RBAC)</h4>
                <small class="text-muted">Pengaturan Akun Pengguna: Admin, Supervisor, dan Karyawan</small>
            </div>
            <div>
                <a href="{{ route('monitoring.index') }}" class="btn btn-sm btn-outline-secondary">Kembali ke Monitoring</a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0">Tambah Akun Pengguna Baru</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.user.store') }}" method="POST" class="row g-2">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="Nama..." required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">Username</label>
                        <input type="text" name="username" class="form-control form-control-sm" placeholder="Username..." required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">Password</label>
                        <input type="password" name="password" class="form-control form-control-sm" placeholder="Min. 6 digit" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">Role</label>
                        <select name="role" class="form-select form-select-sm" required>
                            <option value="karyawan">Karyawan</option>
                            <option value="supervisor">Supervisor</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Tautkan NIP Pegawai (Opsional)</label>
                        <select name="nip" class="form-select form-select-sm">
                            <option value="">-- Tanpa Tautan NIP --</option>
                            @foreach ($karyawans as $k)
                                <option value="{{ $k->nip }}">{{ $k->nama }} ({{ $k->nip }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 text-end mt-2">
                        <button type="submit" class="btn btn-sm btn-danger">+ Simpan Akun Pengguna</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-0 table-responsive">
                <table class="table table-bordered table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Nama</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>NIP Terhubung</th>
                            <th>Terdaftar Pada</th>
                            <th style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $index => $u)
                            <tr>
                                <td>{{ $users->firstItem() + $index }}</td>
                                <td><strong>{{ $u->name }}</strong></td>
                                <td><code>{{ $u->username }}</code></td>
                                <td>
                                    <span class="badge {{ $u->role == 'admin' ? 'bg-danger' : ($u->role == 'supervisor' ? 'bg-primary' : 'bg-success') }}">
                                        {{ ucfirst($u->role) }}
                                    </span>
                                </td>
                                <td>{{ $u->nip ?? '-' }}</td>
                                <td>{{ $u->created_at?->format('d-m-Y H:i') }}</td>
                                <td>
                                    @if ($u->id !== auth()->id())
                                        <form action="{{ route('admin.user.destroy', $u) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus user {{ $u->username }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                        </form>
                                    @else
                                        <small class="text-muted">(Akun Anda)</small>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-3 text-muted">Belum ada akun pengguna.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</body>
</html>
