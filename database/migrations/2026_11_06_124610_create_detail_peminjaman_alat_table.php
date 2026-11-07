<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('detail_peminjaman_alat', function (Blueprint $table) {
            $table->id();

            $table->foreignId('peminjaman_alat_id')
                ->constrained('peminjaman_alat')
                ->restrictOnDelete();

            $table->foreignId('alat_id')
                ->constrained('alat')
                ->restrictOnDelete();

            $table->unsignedInteger('jumlah');

            $table->timestamps();

            $table->unique(['peminjaman_alat_id', 'alat_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_peminjaman_alat');
    }
};
