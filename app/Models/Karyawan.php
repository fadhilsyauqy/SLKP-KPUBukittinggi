<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Karyawan extends Model
{
    use HasFactory;

    /**
     * Nama tabel di database.
     *
     * @var string
     */
    protected $table = 'karyawans';

    /**
     * Daftar pilihan Sub Bagian resmi di lingkungan Sekretariat KPU Kota Bukittinggi.
     *
     * @var array<string, string>
     */
    public const SUB_BAGIAN_LIST = [
        'Teknis' => 'Sub Bagian Teknis Penyelenggaraan Pemilu & Hupmas',
        'Hukum & SDM' => 'Sub Bagian Hukum & SDM',
        'KUL' => 'Sub Bagian Keuangan, Umum & Logistik (KUL)',
        'Rendatin' => 'Sub Bagian Perencanaan, Data & Informasi (Rendatin)',
        'Sekretariat' => 'Sekretariat & Pimpinan',
    ];

    /**
     * Dapatkan daftar pilihan Sub Bagian resmi.
     *
     * @return array<string, string>
     */
    public static function getSubBagianList(): array
    {
        return self::SUB_BAGIAN_LIST;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nip',
        'nama',
        'jabatan_id',
        'sub_bagian',
        'sort_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * Relasi ke Master Jabatan.
     */
    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class, 'jabatan_id');
    }

    /**
     * Relasi ke Akun Pengguna / User (jika ada).
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'nip', 'nip');
    }

    /**
     * Relasi ke riwayat Laporan Kinerja / Presensi.
     */
    public function laporans(): HasMany
    {
        return $this->hasMany(Laporan::class, 'nip', 'nip');
    }

    /**
     * Dapatkan Sub Bagian efektif karyawan (menggunakan sub_bagian milik karyawan,
     * atau fallback ke sub_bagian pada jabatan terkait jika belum diatur secara spesifik).
     */
    public function getEffectiveSubBagianAttribute(): ?string
    {
        return $this->sub_bagian ?: $this->jabatan?->sub_bagian;
    }

    /**
     * Scope query untuk memfilter pegawai berdasarkan Sub Bagian (baik langsung maupun via jabatan).
     */
    public function scopeSubBagian(Builder $query, string $subBagian): Builder
    {
        return $query->where(function (Builder $q) use ($subBagian) {
            $q->where('sub_bagian', $subBagian)
                ->orWhere(function (Builder $q2) use ($subBagian) {
                    $q2->whereNull('sub_bagian')
                        ->whereHas('jabatan', function (Builder $j) use ($subBagian) {
                            $j->where('sub_bagian', $subBagian);
                        });
                });
        });
    }

    /**
     * Scope query untuk mengurutkan karyawan berdasarkan hierarki jabatan (sort_order ASC),
     * kemudian berdasarkan nama (nama ASC).
     */
    public function scopeHierarchy(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('nama', 'asc');
    }

    /**
     * Helper untuk menentukan sort_order hierarki KPU Bukittinggi secara otomatis:
     * 1: Sekretaris
     * 2: Kasubag
     * 3: Penelaah
     * 4: Penyusun
     * 5: Pengolah
     * 6: Ahli
     * 7: Operator
     * 8: Pengelola
     * 10: Default / Staff Pelaksana
     */
    public static function determineSortOrder(?string $namaJabatan): int
    {
        if (empty($namaJabatan)) {
            return 10;
        }

        if (stripos($namaJabatan, 'Sekretaris') === 0) {
            return 1;
        } elseif (stripos($namaJabatan, 'Kasubag') === 0) {
            return 2;
        } elseif (stripos($namaJabatan, 'Penelaah') === 0) {
            return 3;
        } elseif (stripos($namaJabatan, 'Penyusun') === 0) {
            return 4;
        } elseif (stripos($namaJabatan, 'Pengolah') === 0) {
            return 5;
        } elseif (stripos($namaJabatan, 'Ahli') === 0) {
            return 6;
        } elseif (stripos($namaJabatan, 'Operator') === 0) {
            return 7;
        } elseif (stripos($namaJabatan, 'Pengelola') === 0) {
            return 8;
        } elseif (stripos($namaJabatan, 'Pengadministrasi') === 0) {
            return 9;
        }

        return 10;
    }
}
