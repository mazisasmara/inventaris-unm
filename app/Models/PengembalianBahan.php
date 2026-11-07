<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PengembalianBahan extends Model
{
    protected $table = 'pengembalian_bahan';

    protected $fillable = [
        'pengambilan_bahan_id',
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

    public function pengambilanBahan(): BelongsTo
    {
        return $this->belongsTo(
            PengambilanBahan::class,
            'pengambilan_bahan_id'
        );
    }

    public function pemroses(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }

    public function detail(): HasMany
    {
        return $this->hasMany(
            DetailPengembalianBahan::class,
            'pengembalian_bahan_id'
        );
    }
}
