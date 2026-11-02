@extends('layouts.app')

@section('title', 'Tambah Karyawan - SLKP KPU Bukittinggi')
@section('page-title', 'Tambah Karyawan Baru')

@section('content')
<div class="container-fluid" style="max-width: 700px;">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom py-3 d-flex align-items-center">
            <div class="rounded-circle bg-danger-subtle text-danger p-2 me-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                <i class="bi bi-person-plus-fill fs-5"></i>
            </div>
            <div>
                <h5 class="mb-0 fw-bold text-dark">Tambah Data Karyawan Baru</h5>
                <small class="text-muted">Daftarkan pegawai sekretariat KPU Kota Bukittinggi ke sistem</small>
            </div>
        </div>

        <div class="card-body p-4">
            <form action="{{ route('admin.karyawan.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="nip" class="form-label fw-semibold">NIP Pegawai <span class="text-danger">*</span></label>
                    <input type="text"
                           name="nip"
                           id="nip"
                           class="form-control @error('nip') is-invalid @enderror"
                           value="{{ old('nip') }}"
                           placeholder="Contoh: 197001012000011001"
                           required>
                    @error('nip')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="nama" class="form-label fw-semibold">Nama Lengkap & Gelar <span class="text-danger">*</span></label>
                    <input type="text"
                           name="nama"
                           id="nama"
                           class="form-control @error('nama') is-invalid @enderror"
                           value="{{ old('nama') }}"
                           placeholder="Contoh: Nama Pejabat, S.STP"
                           required>
                    @error('nama')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="jabatan_id" class="form-label fw-semibold">Jabatan Pegawai <span class="text-danger">*</span></label>
                    <select name="jabatan_id" id="jabatan_id" class="form-select @error('jabatan_id') is-invalid @enderror" required>
                        <option value="">-- Pilih Jabatan --</option>
                        @foreach ($jabatans as $jabatan)
                            <option value="{{ $jabatan->id }}" {{ old('jabatan_id') == $jabatan->id ? 'selected' : '' }}>
                                {{ $jabatan->nama_jabatan }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text text-muted mt-2">
                        <i class="bi bi-info-circle me-1"></i> Nilai prioritas urutan hierarki (<em>sort_order</em>: Sekretaris = 1, Kasubag = 2, Penelaah = 3, dst.) akan dihitung secara otomatis oleh sistem berdasarkan jabatan yang dipilih.
                    </div>
                    @error('jabatan_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <a href="{{ route('admin.karyawan.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Batal & Kembali
                    </a>
                    <button type="submit" class="btn btn-danger px-4">
                        <i class="bi bi-check-circle-fill me-1"></i> Simpan Karyawan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
