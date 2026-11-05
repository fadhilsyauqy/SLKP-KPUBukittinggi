<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HariLibur extends Model
{
    use HasFactory;

    /**
     * Nama tabel di database.
     *
     * @var string
     */
    protected $table = 'hari_liburs';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tanggal',
        'keterangan',
        'is_cuti_bersama',
        'is_hari_kerja',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tanggal' => 'date',
        'is_cuti_bersama' => 'boolean',
        'is_hari_kerja' => 'boolean',
    ];

    /**
     * Scope query untuk filter data berdasarkan tahun.
     */
    public function scopeTahun(Builder $query, int $tahun): Builder
    {
        return $query->whereYear('tanggal', $tahun);
    }

    /**
     * Scope query untuk filter data berdasarkan bulan dan tahun.
     */
    public function scopeBulan(Builder $query, int $tahun, int $bulan): Builder
    {
        return $query->whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan);
    }

    /**
     * Scope query khusus untuk cuti bersama.
     */
    public function scopeCutiBersama(Builder $query): Builder
    {
        return $query->where('is_cuti_bersama', true)->where('is_hari_kerja', false);
    }

    /**
     * Scope query khusus untuk hari libur nasional resmi (non-cuti bersama).
     */
    public function scopeLiburNasional(Builder $query): Builder
    {
        return $query->where('is_cuti_bersama', false)->where('is_hari_kerja', false);
    }

    /**
     * Scope query khusus untuk Hari Kerja Khusus (Sabtu/Minggu aktif).
     */
    public function scopeHariKerjaKhusus(Builder $query): Builder
    {
        return $query->where('is_hari_kerja', true);
    }

    /**
     * Periksa apakah suatu tanggal merupakan hari libur nasional atau cuti bersama.
     */
    public static function isHoliday(CarbonInterface|string $date): bool
    {
        $dateStr = $date instanceof CarbonInterface ? $date->format('Y-m-d') : Carbon::parse($date)->format('Y-m-d');

        return static::whereDate('tanggal', $dateStr)->where('is_hari_kerja', false)->exists();
    }

    /**
     * Periksa apakah suatu tanggal merupakan Hari Kerja Khusus (Sabtu/Minggu aktif).
     */
    public static function isHariKerjaKhusus(CarbonInterface|string $date): bool
    {
        $dateStr = $date instanceof CarbonInterface ? $date->format('Y-m-d') : Carbon::parse($date)->format('Y-m-d');

        return static::whereDate('tanggal', $dateStr)->where('is_hari_kerja', true)->exists();
    }

    /**
     * Dapatkan instance HariLibur pada tanggal tertentu jika ada.
     */
    public static function getHoliday(CarbonInterface|string $date): ?self
    {
        $dateStr = $date instanceof CarbonInterface ? $date->format('Y-m-d') : Carbon::parse($date)->format('Y-m-d');

        return static::whereDate('tanggal', $dateStr)->first();
    }

    /**
     * Ambil pemetaan seluruh hari libur dalam bulan dan tahun tertentu dengan key 'Y-m-d'.
     *
     * @return array<string, self>
     */
    public static function getHolidaysMap(int $year, int $month): array
    {
        $holidays = static::whereYear('tanggal', $year)
            ->whereMonth('tanggal', $month)
            ->get();

        $map = [];
        foreach ($holidays as $holiday) {
            $key = $holiday->tanggal?->format('Y-m-d');
            if ($key) {
                $map[$key] = $holiday;
            }
        }

        return $map;
    }

    /**
     * Hitung jumlah hari kerja efektif antara dua tanggal (inklusif).
     * Otomatis mengecualikan hari akhir pekan (Sabtu & Minggu) dan Hari Libur / Cuti Bersama yang tercatat.
     */
    public static function countWorkingDays(CarbonInterface|string $startDate, CarbonInterface|string $endDate): int
    {
        $start = $startDate instanceof CarbonInterface ? $startDate->copy()->startOfDay() : Carbon::parse($startDate)->startOfDay();
        $end = $endDate instanceof CarbonInterface ? $endDate->copy()->startOfDay() : Carbon::parse($endDate)->startOfDay();

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        $records = static::whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])->get();
        $holidayDates = [];
        $specialWorkDates = [];
        foreach ($records as $r) {
            $d = $r->tanggal?->format('Y-m-d');
            if ($d) {
                if ($r->is_hari_kerja) {
                    $specialWorkDates[$d] = true;
                } else {
                    $holidayDates[$d] = true;
                }
            }
        }

        $workingDays = 0;
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $isWeekend = $cursor->isWeekend();
            $dateStr = $cursor->format('Y-m-d');
            $isSpecialWork = isset($specialWorkDates[$dateStr]);
            $isHoliday = isset($holidayDates[$dateStr]);

            if ($isSpecialWork) {
                $workingDays++;
            } elseif (! $isWeekend && ! $isHoliday) {
                $workingDays++;
            }

            $cursor->addDay();
        }

        return $workingDays;
    }

    /**
     * Hitung total hari kerja efektif dalam suatu bulan dan tahun tertentu.
     */
    public static function getEffectiveWorkingDays(int $year, int $month): int
    {
        $carbonMonth = Carbon::createFromDate($year, $month, 1);
        $start = $carbonMonth->copy()->startOfMonth();
        $end = $carbonMonth->copy()->endOfMonth();

        return static::countWorkingDays($start, $end);
    }
}
