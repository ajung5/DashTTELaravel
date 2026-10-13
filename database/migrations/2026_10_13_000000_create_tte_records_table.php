<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tte_records', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('source_row')->unique();
            $table->string('sync_batch', 36)->index();
            $table->string('nama')->nullable()->index();
            $table->string('nik', 32)->nullable();
            $table->string('email')->nullable();
            $table->string('unit_kerja')->nullable()->index();
            $table->string('nama_unor')->nullable();
            $table->string('status_kepegawaian')->nullable()->index();
            $table->string('status_pegawai')->nullable()->index();
            $table->string('status_pengguna')->nullable()->index();
            $table->string('status_sertifikat')->nullable()->index();
            $table->date('tanggal_berakhir')->nullable()->index();
            $table->string('rumpun_jabatan')->nullable();
            $table->string('jenis_jabatan')->nullable();
            $table->timestamps();
            $table->index(['unit_kerja', 'status_sertifikat']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tte_records');
    }
};
