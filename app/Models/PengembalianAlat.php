<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PengembalianAlat extends Model
{
    protected $table = 'pengembalian_alat';

    protected $fillable = [
        'peminjaman_alat_id',
        'diproses_oleh',
        'tanggal_pengembalian',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pengembalian' => 'datetime',
        ];
    }

    public function peminjamanAlat(): BelongsTo
    {
        return $this->belongsTo(
            PeminjamanAlat::class,
            'peminjaman_alat_id'
        );
    }

    public function pemroses(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }

    public function detail(): HasMany
    {
        return $this->hasMany(
            DetailPengembalianAlat::class,
            'pengembalian_alat_id'
        );
    }
}