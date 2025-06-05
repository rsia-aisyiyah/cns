<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MapingDokterDpjpvclaim extends Model
{
    protected $connection = 'mysql';

    protected $table = 'maping_dokter_dpjpvclaim';

    protected $primaryKey = 'kd_dokter';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;
}
