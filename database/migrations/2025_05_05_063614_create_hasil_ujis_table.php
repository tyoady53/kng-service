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
            $table->string('id_kendaraan',10);
            $table->string('fotodepan');
            $table->string('fotobelakang');
            $table->string('fotokanan');
            $table->string('fotokiri');
            $table->string('emisiasap');
            $table->string('emisico');
            $table->string('emisihc');
            $table->string('totalgayapengereman');
            $table->string('selisihgayapengereman1');
            $table->string('selisihgayapengereman2');
            $table->string('selisihgayapengereman3');
            $table->string('selisihgayapengereman4');
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
            $table->string('masaberlakuuji');
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
