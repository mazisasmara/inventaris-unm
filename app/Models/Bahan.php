<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bahan extends Model
{
    //
    public function detailPengambilan(): HasMany
    {
        return $this->hasMany(
            DetailPengambilanBahan::class,
            'bahan_id'
        );
    }
}


