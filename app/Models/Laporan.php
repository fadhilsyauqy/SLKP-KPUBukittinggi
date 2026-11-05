<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

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
        'uuid',
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
        'nomor_spt',
        'keterangan',
        'alasan',
        'tanggal_selesai',
        'lama_hari',
        'bukti',
        'status_approval',
        'approved_by',
        'approved_at',
        'catatan_approval',
        'user_read_at',
        'status_sinkron_google',
        'catatan_sinkron',
    ];

    /**
     * Boot the model and set automatic UUID on creation.
     */
    protected static function booted(): void
    {
        static::creating(function (Laporan $laporan) {
            if (empty($laporan->uuid)) {
                $laporan->uuid = (string) Str::uuid();
            }
        });
    }

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
            'user_read_at' => 'datetime',
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
     * Scope query untuk memfilter laporan berdasarkan Sub Bagian pegawai.
     */
    public function scopeSubBagian(Builder $query, string $subBagian): Builder
    {
        return $query->whereHas('karyawan', function (Builder $k) use ($subBagian) {
            $k->where(function (Builder $q) use ($subBagian) {
                $q->where('sub_bagian', $subBagian)
                    ->orWhere(function (Builder $q2) use ($subBagian) {
                        $q2->whereNull('sub_bagian')
                            ->whereHas('jabatan', function (Builder $j) use ($subBagian) {
                                $j->where('sub_bagian', $subBagian);
                            });
                    });
            });
        });
    }

    /**
     * Dapatkan Sub Bagian pegawai pembuat laporan.
     */
    public function getSubBagianAttribute(): ?string
    {
        return $this->karyawan?->effective_sub_bagian;
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
     * Cek apakah notifikasi keputusan permohonan sudah dibaca pegawai.
     */
    public function isReadByUser(): bool
    {
        return $this->user_read_at !== null;
    }

    /**
     * Scope query untuk permohonan yang keputusannya belum dibaca oleh pegawai.
     */
    public function scopeUnreadByUser(Builder $query): Builder
    {
        return $query->whereNull('user_read_at');
    }

    /**
     * Scope query untuk mengurutkan laporan dari yang paling baru.
     */
    public function scopeTerbaru(Builder $query): Builder
    {
        return $query->orderBy('tanggal', 'desc')->orderBy('id', 'desc');
    }
}
