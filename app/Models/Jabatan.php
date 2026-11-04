<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jabatan extends Model
{
    use HasFactory;

    /**
     * Nama tabel di database.
     *
     * @var string
     */
    protected $table = 'jabatans';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nama_jabatan',
        'sub_bagian',
    ];

    /**
     * Daftar pilihan Sub Bagian resmi di lingkungan Sekretariat KPU Kota Bukittinggi.
     *
     * @return array<string, string>
     */
    public static function getSubBagianList(): array
    {
        return Karyawan::getSubBagianList();
    }

    /**
     * Relasi ke seluruh Karyawan yang memiliki jabatan ini.
     */
    public function karyawans(): HasMany
    {
        return $this->hasMany(Karyawan::class, 'jabatan_id');
    }
}
