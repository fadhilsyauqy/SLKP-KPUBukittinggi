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
        'status_approval',
        'approved_by',
        'approved_at',
        'catatan_approval',
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
            'approved_at' => 'datetime',
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
     * Relasi ke User atasan/pejabat yang menyetujui/menolak laporan.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
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
     * Scope query untuk permohonan yang berstatus pending (menunggu persetujuan).
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status_approval', 'pending');
    }

    /**
     * Scope query untuk permohonan yang sudah disetujui.
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status_approval', 'approved');
    }

    /**
     * Scope query untuk permohonan yang ditolak.
     */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status_approval', 'rejected');
    }

    /**
     * Cek apakah status approval masih pending.
     */
    public function isPending(): bool
    {
        return $this->status_approval === 'pending';
    }

    /**
     * Cek apakah laporan telah disetujui.
     */
    public function isApproved(): bool
    {
        return $this->status_approval === 'approved';
    }

    /**
     * Cek apakah laporan ditolak.
     */
    public function isRejected(): bool
    {
        return $this->status_approval === 'rejected';
    }

    /**
     * Scope query untuk mengurutkan laporan dari yang paling baru.
     */
    public function scopeTerbaru(Builder $query): Builder
    {
        return $query->orderBy('tanggal', 'desc')->orderBy('id', 'desc');
    }
}
