<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_laboratorium', function (Blueprint $table) {
            $table->id();

            $table->foreignId('laboratorium_id')
                ->constrained('laboratorium')
                ->restrictOnDelete();

            $table->string('hari');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['laboratorium_id', 'hari']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_laboratorium');
    }
};
