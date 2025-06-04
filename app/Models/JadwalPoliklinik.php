<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JadwalPoliklinik extends Model
{
    use \Awobaz\Compoships\Compoships;

    
    protected $connection = 'mysql';

    protected $table = 'jadwal';

    protected $primaryKey = ['kd_dokter', 'hari_kerja', 'jam_mulai'];

    // protected $guarded = [];

    public $timestamps = false;

    public $incrementing = false;


    public function poliklinik()
    {
        return $this->belongsTo(Poliklinik::class, 'kd_poli', 'kd_poli');
    }

    public function dokter()
    {
        return $this->belongsTo(Dokter::class, 'kd_dokter', 'kd_dokter')->select('kd_dokter', 'nm_dokter', 'kd_sps');
    }
}
