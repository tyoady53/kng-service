<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kendaraan extends Model
{
    use HasFactory;
    protected $guarded = [
        'id',
    ];

    public function detail()
    {
        return $this->hasOne(KendaraanDetail::class, 'generated_id', 'kendaraan_id')->latest();
    }

    public function hasil_uji()
    {
        return $this->hasMany(HasilUji::class, 'generated_id', 'kendaraan_id');
    }

    public function hasil_terakhir()
    {
        return $this->hasOne(HasilUji::class, 'generated_id', 'kendaraan_id')->latest();
    }
}
