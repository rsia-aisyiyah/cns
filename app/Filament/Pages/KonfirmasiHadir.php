<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class KonfirmasiHadir extends Page
{
    use \BezhanSalleh\FilamentShield\Traits\HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    protected static ?string $navigationGroup = 'Notifikasi Pasien';
    
    protected static string $view = 'filament.pages.konfirmasi-hadir';

    // TODO : Ambil pasien registasi hari ini, semua pasien.
}
