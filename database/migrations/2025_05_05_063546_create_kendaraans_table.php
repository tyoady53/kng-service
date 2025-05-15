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
        Schema::create('kendaraans', function (Blueprint $table) {
            $table->id();
            $table->string('generated_id',10);
            $table->string('no_kendaraan',10);
            $table->string('no_uji');
            $table->string('nama');
            $table->string('nosertifikatreg');
            $table->string('tglsertifikatreg');
            $table->string('norangka');
            $table->string('nomesin');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kendaraans');
    }
};
