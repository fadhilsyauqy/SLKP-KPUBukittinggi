@extends('layouts.app')

@section('title', 'Tambah Pegawai - SLKP KPU Bukittinggi')
@section('page-title', 'Tambah Pegawai')

@section('content')
<div class="container-fluid" style="max-width: 680px;">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center">
            <div class="rounded-circle bg-danger bg-opacity-10 text-danger p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                <i class="bi bi-person-plus-fill fs-5"></i>
            </div>
            <div>
                <h6 class="mb-0 fw-bold text-dark">Tambah Data Pegawai Baru</h6>
                <small class="text-muted">Daftarkan pegawai sekretariat KPU Kota Bukittinggi</small>
            </div>
        </div>

        <div class="card-body p-4">
            <form action="{{ route('admin.karyawan.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="nip" class="form-label">NIP Pegawai <span class="text-danger">*</span></label>
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
                    <label for="nama" class="form-label">Nama Lengkap &amp; Gelar <span class="text-danger">*</span></label>
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
                    <label for="jabatan_id" class="form-label">Jabatan Pegawai <span class="text-danger">*</span></label>
                    <select name="jabatan_id" id="jabatan_id" class="form-select @error('jabatan_id') is-invalid @enderror" required>
                        <option value="">-- Pilih Jabatan --</option>
                        @foreach ($jabatans as $jabatan)
                            <option value="{{ $jabatan->id }}" {{ old('jabatan_id') == $jabatan->id ? 'selected' : '' }}>
                                {{ $jabatan->nama_jabatan }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text text-muted small mt-1.5">
                        <i class="bi bi-info-circle me-1"></i> Nilai urutan hierarki (<em>sort_order</em>) dihitung otomatis oleh sistem.
                    </div>
                    @error('jabatan_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <a href="{{ route('admin.karyawan.index') }}" class="btn btn-light text-secondary">
                        <i class="bi bi-arrow-left"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-kpu px-4">
                        <i class="bi bi-check-lg"></i> Simpan Pegawai
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
