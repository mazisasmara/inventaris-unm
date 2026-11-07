<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DetailPengambilanBahan extends Model
{
    protected $table = 'detail_pengambilan_bahan';

    protected $fillable = [
        'pengambilan_bahan_id',
        'bahan_id',
        'jumlah',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
        ];
    }

    public function pengambilanBahan(): BelongsTo
    {
        return $this->belongsTo(
            PengambilanBahan::class,
            'pengambilan_bahan_id'
        );
    }

    public function bahan(): BelongsTo
    {
        return $this->belongsTo(Bahan::class, 'bahan_id');
    }

    public function detailPengembalian(): HasMany
    {
        return $this->hasMany(
            DetailPengembalianBahan::class,
            'detail_pengambilan_bahan_id'
        );
    }
}