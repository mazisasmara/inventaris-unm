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
        Schema::create('pengambilan_bahan', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('diproses_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('tujuan');
            $table->string('status')->default('menunggu');

            $table->timestamp('tanggal_pengajuan')->useCurrent();
            $table->timestamp('diproses_at')->nullable();
            $table->timestamp('selesai_at')->nullable();
            $table->timestamp('dibatalkan_at')->nullable();

            $table->text('catatan_admin')->nullable();

            $table->timestamps();

            $table->index('status');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengambilan_bahan');
    }
};
