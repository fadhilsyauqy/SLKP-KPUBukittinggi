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
        Schema::table('jabatans', function (Blueprint $table) {
            $table->string('sub_bagian')->nullable()->after('nama_jabatan');
        });

        Schema::table('karyawans', function (Blueprint $table) {
            $table->string('sub_bagian')->nullable()->after('jabatan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('karyawans', function (Blueprint $table) {
            $table->dropColumn('sub_bagian');
        });

        Schema::table('jabatans', function (Blueprint $table) {
            $table->dropColumn('sub_bagian');
        });
    }
};
