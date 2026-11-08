
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
        Schema::table('pengambilan_bahan', function (Blueprint $table) {
            $table->timestamp('diserahkan_at')->nullable()->after('diproses_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengambilan_bahan', function (Blueprint $table) {
            $table->dropColumn('diserahkan_at');
        });
    }
};
