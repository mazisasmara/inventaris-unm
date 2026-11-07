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
        Schema::create('detail_pengembalian_alat', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pengembalian_alat_id')
                ->constrained('pengembalian_alat')
                ->restrictOnDelete();

            $table->foreignId('unit_peminjaman_alat_id')
                ->unique()
                ->constrained('unit_peminjaman_alat')
                ->restrictOnDelete();

            $table->string('kondisi_saat_kembali');
            $table->text('catatan')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_pengembalian_alat');
    }
};
