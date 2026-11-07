<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JadwalLaboratorium extends Model
{
    protected $table = 'jadwal_laboratorium';

    protected $fillable = [
        'laboratorium_id',
        'hari',
        'jam_mulai',
        'jam_selesai',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function laboratorium(): BelongsTo
    {
        return $this->belongsTo(Laboratorium::class, 'laboratorium_id');
    }
}