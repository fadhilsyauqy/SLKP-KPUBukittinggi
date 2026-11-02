<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('laporans', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id')->index();
        });

        // Isi UUID unik untuk seluruh data laporan yang sudah ada
        $existingLaporans = DB::table('laporans')->whereNull('uuid')->pluck('id');
        foreach ($existingLaporans as $id) {
            DB::table('laporans')->where('id', $id)->update(['uuid' => (string) Str::uuid()]);
        }

        // Terapkan unique constraint setelah data terisi
        Schema::table('laporans', function (Blueprint $table) {
            $table->unique('uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laporans', function (Blueprint $table) {
            $table->dropUnique(['uuid']);
            $table->dropColumn('uuid');
        });
    }
};
