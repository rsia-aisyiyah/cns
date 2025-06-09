<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BridgingSuratKontrolBPJS extends Model
{
    protected $connection = 'mysql';

    protected $table = 'bridging_surat_kontrol_bpjs';

    protected $primaryKey = 'no_surat';

    protected $keyType = 'string';

    protected $casts = [
        'tgl_surat' => 'date',
        'tgl_rencana' => 'date',
    ];

    public $timestamps = false;

    public $incrementing = false;


    public function sep()
    {
        return $this->belongsTo(BridgingSep::class, 'no_sep', 'no_sep');
    }

    public function sep2()
    {
        return $this->belongsTo(BridgingSep::class, 'no_surat', 'noskdp');
    }

    public function referensiJkn()
    {
        return $this->belongsTo(ReferensiMobileJknBPJS::class, 'no_surat', 'nomorreferensi');
    }

    public function mapingDokter()
    {
        return $this->hasOne(MapingDokterDpjpvclaim::class, 'kd_dokter_bpjs', 'kd_dokter_bpjs');
    }

    public function dokter()
    {
        return $this->hasOneThrough(
            Dokter::class,
            MapingDokterDpjpvclaim::class,
            'kd_dokter_bpjs', // Foreign key on MapingDokterDpjpvclaim
            'kd_dokter', // Foreign key on Dokter
            'kd_dokter_bpjs', // Local key on BridgingSuratKontrolBPJS
            'kd_dokter' // Local key on MapingDokterDpjpvclaim
        );
    }
}
