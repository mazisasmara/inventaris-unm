<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PengambilanBahan extends Model
{
    protected $table = 'pengambilan_bahan';

    protected $fillable = [
        'user_id',
        'diproses_oleh',
        'tujuan',
        'status',
        'tanggal_pengajuan',
        'diproses_at',
        'diserahkan_at',
        'selesai_at',
        'dibatalkan_at',
        'catatan_admin',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pengajuan' => 'datetime',
            'diproses_at' => 'datetime',
            'diserahkan_at' => 'datetime',
            'selesai_at' => 'datetime',
            'dibatalkan_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pemroses(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }

    public function detail(): HasMany
    {
        return $this->hasMany(
            DetailPengambilanBahan::class,
            'pengambilan_bahan_id'
        );
    }

    public function pengembalian(): HasMany
    {
        return $this->hasMany(
            PengembalianBahan::class,
            'pengambilan_bahan_id'
        );
    }
}