<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Undangan extends Model
{
    protected $connection = 'mysql';

    protected $table = 'rsia_undangan';

    protected $primaryKey = 'id';

    public $incrementing = true;

    public $timestamps = true;

    protected $casts = [
        'surat_id' => 'integer',
        'model' => 'string',
        'tipe' => 'string',
        'tanggal' => 'datetime',
        'pj' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'id'
    ];

    protected $guarded = ['id'];
}
