<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Poliklinik extends Model
{
    protected $table = 'poliklinik';

    protected $primaryKey = 'kd_poli';

    protected $guarded = [];

    public $incrementing = false;

    public $timestamps = false;


    public function jadwal_dokter()
    {
        return $this->hasMany(JadwalPoliklinik::class, 'kd_poli', 'kd_poli');
    }
}
