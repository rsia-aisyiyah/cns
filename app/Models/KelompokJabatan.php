<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KelompokJabatan extends Model
{
    protected $connection = 'mysql';

    protected $table = 'kelompok_jabatan';

    protected $primaryKey = 'kode_kelompok';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $guarded = [];


    
    public function pegawai()
    {
        return $this->hasMany(Pegawai::class, 'kode_kelompok', 'kode_kelompok');
    }
}
