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
        Schema::create('detail_pengambilan_bahan', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pengambilan_bahan_id')
                ->constrained('pengambilan_bahan')
                ->restrictOnDelete();

            $table->foreignId('bahan_id')
                ->constrained('bahan')
                ->restrictOnDelete();

            $table->unsignedInteger('jumlah');

            $table->timestamps();

            $table->unique(['pengambilan_bahan_id', 'bahan_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_pengambilan_bahan');
    }
};
