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
        return $this->hasOne(KendaraanDetail::class, 'kendaraan_id', 'generated_id')->latest();
    }

    public function hasil_uji()
    {
        return $this->hasMany(HasilUji::class, 'kendaraan_id', 'generated_id');
    }

    public function hasil_terakhir()
    {
        return $this->hasOne(HasilUji::class, 'kendaraan_id', 'generated_id')->latest();
    }
}
