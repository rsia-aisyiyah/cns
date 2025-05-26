<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jabatan extends Model
{
    protected $connection = 'mysql_2';

    protected $table = 'jabatan';

    protected $primaryKey = 'kd_jbtn';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $guarded = [];


    
    public function petugas()
    {
        return $this->hasMany(Petugas::class, 'kd_jbtn', 'kd_jbtn');
    }
}
