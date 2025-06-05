<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferensiMobileJknBPJS extends Model
{
    protected $connection = 'mysql';

    protected $table = 'referensi_mobilejkn_bpjs';

    protected $primaryKey = 'nobooking';

    protected $keyType = 'string';

    protected $casts = [
        'no_rawat' => 'string',
    ];

    public $timestamps = false;

    public $incrementing = false;


    public function sep()
    {
        return $this->belongsTo(BridgingSep::class, 'no_sep', 'no_sep');
    }
}
