
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detail_pengembalian_bahan', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pengembalian_bahan_id')
                ->constrained('pengembalian_bahan')
                ->restrictOnDelete();

            $table->foreignId('detail_pengambilan_bahan_id')
                ->constrained('detail_pengambilan_bahan')
                ->restrictOnDelete();

            $table->unsignedInteger('jumlah_dikembalikan');

            $table->unsignedInteger('jumlah_layak')->default(0);

            $table->text('catatan')->nullable();

            $table->timestamps();

            $table->unique(
                ['pengembalian_bahan_id', 'detail_pengambilan_bahan_id'],
                'dpb_pengembalian_detail_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_pengembalian_bahan');
    }
};
