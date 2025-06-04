<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResikoKerja extends Model
{
    protected $connection = 'mysql';

    protected $table = 'resiko_kerja';

    protected $primaryKey = 'kode_resiko';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $guarded = [];


    
    public function pegawai()
    {
        return $this->hasMany(Pegawai::class, 'kode_resiko', 'kode_resiko');
    }
}
