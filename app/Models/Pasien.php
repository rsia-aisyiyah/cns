<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pasien extends Model
{
    protected $connection = 'mysql';

    protected $table = 'pasien';

    protected $primaryKey = 'no_rkm_medis';

    protected $guarded = [];

    protected $casts = [
        'no_rkm_medis' => 'string',
        'kd_pj' => 'string',
    ];

    public $timestamps = false;

    public $incrementing = false;
}
