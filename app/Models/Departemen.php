<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Departemen extends Model
{
    protected $connection = 'mysql';

    protected $table = 'departemen';

    protected $primaryKey = 'dep_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $guarded = [];


    
    public function pegawai()
    {
        return $this->hasMany(Pegawai::class, 'departemen', 'dep_id');
    }
}
