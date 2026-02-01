<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HasilUji extends Model
{
    use HasFactory;
    protected $connection = 'pgsql_eblue';
    protected $table = 'datapengujian';
    // pgsql_eblue
    protected $guarded = [
        'id',
    ];
    protected $hidden = ['id'];
}
