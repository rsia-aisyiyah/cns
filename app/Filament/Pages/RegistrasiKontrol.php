<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class RegistrasiKontrol extends Page
{
    use \BezhanSalleh\FilamentShield\Traits\HasPageShield;
    
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Notifikasi Pasien';

    protected static string $view = 'filament.pages.registrasi-kontrol';

    // TODO : Ambil data pasien yang memilki surat kontrol tapi belum melakukan registrasi periksa.
}
