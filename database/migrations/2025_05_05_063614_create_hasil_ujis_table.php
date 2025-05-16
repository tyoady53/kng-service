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
        Schema::create('hasil_ujis', function (Blueprint $table) {
            $table->id();
            $table->string('id_kendaraan',50);
            $table->string('fotodepan')->nullable();
            $table->string('fotobelakang')->nullable();
            $table->string('fotokanan')->nullable();
            $table->string('fotokiri')->nullable();
            $table->string('emisiasap')->nullable();
            $table->string('emisico')->nullable();
            $table->string('emisihc')->nullable();
            $table->string('totalgayapengereman');
            $table->string('selisihgayapengereman1')->nullable();
            $table->string('selisihgayapengereman2')->nullable();
            $table->string('selisihgayapengereman3')->nullable();
            $table->string('selisihgayapengereman4')->nullable();
            $table->string('remparkirtangan');
            $table->string('remparkirkaki');
            $table->string('kincuprodadepan');
            $table->string('tingkatkebisingan');
            $table->string('kekuatanpancarlampukanan');
            $table->string('kekuatanpancarlampukiri');
            $table->string('penyimpanganlampukanan');
            $table->string('penyimpanganlampukiri');
            $table->string('penunjukkecepatan');
            $table->string('kedalamanalurban');
            $table->date('masaberlakuuji');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hasil_ujis');
    }
};
