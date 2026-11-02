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
        Schema::create('laporans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nip')->nullable()->index();
            $table->string('nama');
            $table->string('jabatan')->nullable();
            $table->enum('tipe', ['hadir', 'tidak_hadir']);
            $table->date('tanggal');
            $table->time('jam_masuk')->nullable();
            $table->time('jam_keluar')->nullable();
            $table->text('uraian')->nullable();
            $table->text('output')->nullable();
            $table->string('keterangan')->nullable(); // Cuti, Izin, Sakit, Perjadin
            $table->text('alasan')->nullable();
            $table->date('tanggal_selesai')->nullable(); // Untuk laporan rentang hari
            $table->string('lama_hari')->nullable(); // misal "2 Hari"
            $table->string('bukti')->nullable(); // Path file lampiran surat dokter / bukti izin
            $table->boolean('status_sinkron_google')->default(false);
            $table->text('catatan_sinkron')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporans');
    }
};
