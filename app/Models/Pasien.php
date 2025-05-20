<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pasien extends Model
{
    protected $table = 'pasien';

    protected $primaryKey = 'no_rkm_medis';

    protected $hidden = ['no_ktp', 'no_peserta'];

    protected $guarded = [];

    protected $casts = [
        'no_rkm_medis' => 'string',
        'kd_pj' => 'string',
    ];

    public $timestamps = false;

    public $incrementing = false;
}
