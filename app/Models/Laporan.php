<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Laporan extends Model
{
    use HasFactory;

    /**
     * Nama tabel di database.
     *
     * @var string
     */
    protected $table = 'laporans';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'nip',
        'nama',
        'jabatan',
        'tipe',
        'tanggal',
        'jam_masuk',
        'jam_keluar',
        'uraian',
        'output',
        'keterangan',
        'alasan',
        'tanggal_selesai',
        'lama_hari',
        'bukti',
        'status_sinkron_google',
        'catatan_sinkron',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'tanggal_selesai' => 'date',
            'status_sinkron_google' => 'boolean',
        ];
    }

    /**
     * Relasi ke User pembuat laporan.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi ke data Karyawan berdasarkan NIP.
     */
    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'nip', 'nip');
    }

    /**
     * Scope query untuk memfilter laporan kerja hadir saja.
     */
    public function scopeHadir(Builder $query): Builder
    {
        return $query->where('tipe', 'hadir');
    }

    /**
     * Scope query untuk memfilter laporan tidak hadir saja (cuti, izin, sakit, dinas luar).
     */
    public function scopeTidakHadir(Builder $query): Builder
    {
        return $query->where('tipe', 'tidak_hadir');
    }

    /**
     * Scope query untuk mengurutkan laporan dari yang paling baru.
     */
    public function scopeTerbaru(Builder $query): Builder
    {
        return $query->orderBy('tanggal', 'desc')->orderBy('id', 'desc');
    }
}
