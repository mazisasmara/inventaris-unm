<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}