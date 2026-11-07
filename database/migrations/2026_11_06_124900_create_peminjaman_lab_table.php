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
        Schema::create('peminjaman_lab', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('laboratorium_id')
                ->constrained('laboratorium')
                ->restrictOnDelete();

            $table->foreignId('diproses_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('tujuan');

            $table->string('status')->default('menunggu');

            $table->timestamp('tanggal_pengajuan')->useCurrent();

            $table->dateTime('waktu_mulai');
            $table->dateTime('waktu_selesai');

            $table->timestamp('diproses_at')->nullable();

            $table->text('catatan_admin')->nullable();

            $table->timestamps();

            $table->index(['laboratorium_id', 'status']);
            $table->index(['waktu_mulai', 'waktu_selesai']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peminjaman_lab');
    }
};
