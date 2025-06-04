<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dokter extends Model
{
    protected $connection = 'mysql';

    protected $table = 'dokter';

    protected $primaryKey = 'kd_dokter';

    protected $keyType = 'string';

    protected $casts = [
        'kd_dokter' => 'string',
    ];

    protected $guarded = [];

    public $incrementing = false;

    public $timestamps = false;


    public function jadwal()
    {
        return $this->hasMany(JadwalPoliklinik::class, 'kd_dokter', 'kd_dokter');
    }

    public function poliklinik()
    {
        return $this->hasManyThrough(
            Poliklinik::class,
            JadwalPoliklinik::class,
            'kd_dokter', // Foreign key on jadwal_poliklinik table...
            'kd_poli', // Foreign key on poliklinik table...
            'kd_dokter', // Local key on dokter table...
            'kd_poli' // Local key on jadwal_poliklinik table...
        );
    }

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'kd_dokter', 'nik')->select('id', 'nik', 'nama', 'jk', 'photo');
    }

    public function spesialis()
    {
        return $this->belongsTo(Spesialis::class, 'kd_sps', 'kd_sps');
    }
}
