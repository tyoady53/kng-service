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
        Schema::create('kendaraan_details', function (Blueprint $table) {
            $table->id();
            $table->string('id_kendaraan',50);
            $table->string('merek');
            $table->string('tipe');
            $table->string('jenis');
            $table->string('thpembuatan');
            $table->string('bahanbakar');
            $table->string('isisilinder');
            $table->string('dayamotorpenggerak');
            $table->string('jbb');
            $table->string('jbkb');
            $table->string('jbi');
            $table->string('jbki');
            $table->string('mst');
            $table->string('beratkosong');
            $table->string('konfigurasisumburoda');
            $table->string('ukuranban');
            $table->string('panjangkendaraan');
            $table->string('lebarkendaraan');
            $table->string('tinggikendaraan');
            $table->string('panjangbakatautangki');
            $table->string('lebarbakatautangki');
            $table->string('tinggibakatautangki');
            $table->string('julurdepan');
            $table->string('julurbelakang');
            $table->string('jaraksumbu1_2')->nullable();
            $table->string('jaraksumbu2_3')->nullable();
            $table->string('jaraksumbu3_4')->nullable();
            $table->string('dayaangkutorang');
            $table->string('dayaangkutbarang');
            $table->string('kelasjalanterendah');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kendaraan_details');
    }
};
