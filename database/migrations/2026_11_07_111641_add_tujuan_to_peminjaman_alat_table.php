
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peminjaman_alat', function (Blueprint $table) {
            $table->text('tujuan')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('peminjaman_alat', function (Blueprint $table) {
            $table->dropColumn('tujuan');
        });
    }
};
