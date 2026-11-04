@extends('layouts.app')

@section('title', 'Edit Data Pegawai - SLKP KPU Bukittinggi')
@section('page-title', 'Edit Data Pegawai')

@section('content')
<div class="container-fluid px-0 px-md-2">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h5 class="fw-bold text-dark mb-0.5">Edit Data Pegawai</h5>
            <p class="text-muted small mb-0">Perbarui informasi data pegawai dan hierarki jabatan</p>
        </div>
        <a href="{{ route('admin.karyawan.index') }}" class="btn btn-sm btn-light border text-secondary">
            <i class="bi bi-arrow-left"></i> Kembali ke Daftar
        </a>
    </div>

    <!-- Full-Width Horizontal Form Card -->
    <div class="card border-0 shadow-sm w-100">
        <div class="card-body p-4">
            <form action="{{ route('admin.karyawan.update', $karyawan) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nip" class="form-label">NIP Pegawai <span class="text-danger">*</span></label>
                        <input type="text"
                               name="nip"
                               id="nip"
                               class="form-control @error('nip') is-invalid @enderror"
                               value="{{ old('nip', $karyawan->nip) }}"
                               required>
                        @error('nip')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="nama" class="form-label">Nama Lengkap &amp; Gelar <span class="text-danger">*</span></label>
                        <input type="text"
                               name="nama"
                               id="nama"
                               class="form-control @error('nama') is-invalid @enderror"
                               value="{{ old('nama', $karyawan->nama) }}"
                               required>
                        @error('nama')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="jabatan_id" class="form-label">Jabatan Pegawai <span class="text-danger">*</span></label>
                        <select name="jabatan_id" id="jabatan_id" class="form-select @error('jabatan_id') is-invalid @enderror" required>
                            <option value="">-- Pilih Jabatan --</option>
                            @foreach ($jabatans as $jabatan)
                                <option value="{{ $jabatan->id }}" data-sub="{{ $jabatan->sub_bagian }}" {{ old('jabatan_id', $karyawan->jabatan_id) == $jabatan->id ? 'selected' : '' }}>
                                    {{ $jabatan->nama_jabatan }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text text-muted small mt-1.5">
                            <i class="bi bi-info-circle me-1"></i> Saat jabatan diubah, urutan hierarki (<em>sort_order</em>) akan disesuaikan otomatis oleh sistem.
                        </div>
                        @error('jabatan_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="sub_bagian" class="form-label">Sub Bagian / Divisi Kerja <span class="text-muted small">(Opsional)</span></label>
                        <select name="sub_bagian" id="sub_bagian" class="form-select @error('sub_bagian') is-invalid @enderror">
                            <option value="">-- Sesuai Bawaan Jabatan / Bebas --</option>
                            @foreach ($subBagians as $key => $label)
                                <option value="{{ $key }}" {{ old('sub_bagian', $karyawan->sub_bagian) === $key ? 'selected' : '' }}>
                                    {{ $key }} ({{ $label }})
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text text-muted small mt-1.5">
                            <i class="bi bi-diagram-3 me-1"></i> Digunakan untuk pemfilteran laporan &amp; persetujuan tim supervisor.
                        </div>
                        @error('sub_bagian')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-4">
                    <a href="{{ route('admin.karyawan.index') }}" class="btn btn-light text-secondary">
                        <i class="bi bi-arrow-left"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-kpu px-4">
                        <i class="bi bi-check-lg"></i> Perbarui Data Pegawai
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var jabatanSelect = document.getElementById('jabatan_id');
        var subBagianSelect = document.getElementById('sub_bagian');

        if (jabatanSelect && subBagianSelect) {
            jabatanSelect.addEventListener('change', function () {
                var selectedOption = this.options[this.selectedIndex];
                var defaultSub = selectedOption.getAttribute('data-sub');
                if (defaultSub && !subBagianSelect.value) {
                    subBagianSelect.value = defaultSub;
                }
            });
        }
    });
</script>
            </form>
        </div>
    </div>
</div>
@endsection
