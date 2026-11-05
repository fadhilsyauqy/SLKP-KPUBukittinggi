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
        Schema::table('hari_liburs', function (Blueprint $table) {
            $table->boolean('is_hari_kerja')->default(false)->after('is_cuti_bersama');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hari_liburs', function (Blueprint $table) {
            $table->dropColumn('is_hari_kerja');
        });
    }
};
