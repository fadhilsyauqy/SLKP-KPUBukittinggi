<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'action',
        'module',
        'description',
        'ip_address',
        'user_agent',
        'payload',
    ];

    /**
     * Tipe data konversi atribut (casting).
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    /**
     * Relasi ke pengguna yang melakukan aktivitas.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope untuk memfilter log aktivitas berdasarkan kriteria tertentu.
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when(! empty($filters['search']), function (Builder $q) use ($filters) {
                $search = trim($filters['search']);
                $q->where(function (Builder $sub) use ($search) {
                    $sub->where('description', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%")
                        ->orWhereHas('user', function (Builder $userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('username', 'like', "%{$search}%");
                        });
                });
            })
            ->when(! empty($filters['action']), function (Builder $q) use ($filters) {
                $q->where('action', strtoupper(trim($filters['action'])));
            })
            ->when(! empty($filters['module']), function (Builder $q) use ($filters) {
                $q->where('module', strtoupper(trim($filters['module'])));
            })
            ->when(! empty($filters['user_id']), function (Builder $q) use ($filters) {
                $q->where('user_id', $filters['user_id']);
            })
            ->when(! empty($filters['date_from']), function (Builder $q) use ($filters) {
                $q->whereDate('created_at', '>=', $filters['date_from']);
            })
            ->when(! empty($filters['date_to']), function (Builder $q) use ($filters) {
                $q->whereDate('created_at', '<=', $filters['date_to']);
            });
    }
}
