<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pegawai extends Model
{
    protected $connection = 'mysql_2';

    protected $table = 'pegawai';

    protected $primaryKey = 'nik';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $casts = [
        'nik' => 'string',
        'status_koor' => 'boolean',
    ];

    protected $hidden = [
        'id'
    ];

    protected $guarded = ['id'];


    // with no_telp on petugas
    public function scopeWithNoTelp($query)
    {
        return $query->with(['petugas' => function ($q) {
            $q->select('nip', 'no_telp');
        }]);
    }

    public function petugas()
    {
        return $this->hasOne(Petugas::class, 'nip', 'nik');
    }

    public function jenjang()
    {
        return $this->belongsTo(JenjangJabatan::class, 'jnj_jabatan', 'kode');
    }

    public function kelompok()
    {
        return $this->belongsTo(KelompokJabatan::class, 'kode_kelompok', 'kode_kelompok');
    }

    public function resiko()
    {
        return $this->belongsTo(ResikoKerja::class, 'kode_resiko', 'kode_resiko');
    }

    public function unit()
    {
        return $this->belongsTo(Departemen::class, 'departemen', 'dep_id');
    }
}
