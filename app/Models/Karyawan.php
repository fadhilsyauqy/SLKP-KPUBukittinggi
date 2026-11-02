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
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nip',
        'nama',
        'jabatan_id',
        'sort_order',
    ];

    /**
     * Cast attributes.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sort_order' => 'integer',
    ];

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
        }

        return 10;
    }
}
