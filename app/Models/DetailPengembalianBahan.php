<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailPengembalianBahan extends Model
{
    protected $table = 'detail_pengembalian_bahan';

    protected $fillable = [
        'pengembalian_bahan_id',
        'detail_pengambilan_bahan_id',
        'jumlah_dikembalikan',
        'jumlah_layak',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'jumlah_dikembalikan' => 'integer',
            'jumlah_layak' => 'integer',
        ];
    }

    public function pengembalianBahan(): BelongsTo
    {
        return $this->belongsTo(
            PengembalianBahan::class,
            'pengembalian_bahan_id'
        );
    }

    public function detailPengambilanBahan(): BelongsTo
    {
        return $this->belongsTo(
            DetailPengambilanBahan::class,
            'detail_pengambilan_bahan_id'
        );
    }
}
