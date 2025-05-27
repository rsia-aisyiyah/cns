<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JenjangJabatan extends Model
{
    protected $connection = 'mysql_2';

    protected $table = 'jnj_jabatan';

    protected $primaryKey = 'kode';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $guarded = [];


    
    public function pegawai()
    {
        return $this->hasMany(Pegawai::class, 'kode_jenjang', 'kode');
    }
}
