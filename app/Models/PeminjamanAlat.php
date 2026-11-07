<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PeminjamanAlat extends Model
{
    protected $table = 'peminjaman_alat';

    protected $fillable = [
        'user_id',
        'diproses_oleh',
        'status',
        'tujuan',
        'tanggal_pengajuan',
        'tanggal_peminjaman',
        'tanggal_pengembalian_rencana',
        'diproses_at',
        'catatan_admin',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pengajuan' => 'datetime',
            'tanggal_peminjaman' => 'datetime',
            'tanggal_pengembalian_rencana' => 'datetime',
            'diproses_at' => 'datetime',
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
            DetailPeminjamanAlat::class,
            'peminjaman_alat_id'
        );
    }

    public function pengembalian(): HasOne
    {
        return $this->hasOne(
            PengembalianAlat::class,
            'peminjaman_alat_id'
        );
    }
}