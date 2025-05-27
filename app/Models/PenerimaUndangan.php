<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenerimaUndangan extends Model
{
    protected $connection = 'mysql_2';

    protected $table = 'rsia_penerima_undangan';

    protected $primaryKey = 'id';

    public $incrementing = true;

    public $timestamps = true;

    protected $casts = [
        'undangan_id' => 'integer',
        'penerima' => 'string',
    ];

    protected $hidden = [
        'id'
    ];

    protected $guarded = ['id'];
}
