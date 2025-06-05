<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BridgingSep extends Model
{
    protected $connection = 'mysql';

    protected $table = 'bridging_sep';

    protected $primaryKey = 'no_sep';

    protected $casts = [
        'no_rkm_medis' => 'string',
        'kd_pj' => 'string',
    ];

    public $timestamps = false;

    public $incrementing = false;


    public function pasien()
    {
        return $this->belongsTo(Pasien::class, 'nomr', 'no_rkm_medis');
    }

    public function reg_periksa()
    {
        return $this->belongsTo(RegPeriksa::class, 'no_rawat', 'no_rawat');
    }
}
