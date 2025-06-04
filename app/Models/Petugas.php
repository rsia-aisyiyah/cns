<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Petugas extends Model
{
    protected $connection = 'mysql';

    protected $table = 'petugas';

    protected $primaryKey = 'nip';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $casts = [
        'nip' => 'string',
        'status' => 'boolean',
    ];


    
    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'nip', 'nik');
    }

    public function jabatan()
    {
        return $this->belongsTo(Jabatan::class, 'kd_jbtn', 'kd_jbtn');
    }
}
