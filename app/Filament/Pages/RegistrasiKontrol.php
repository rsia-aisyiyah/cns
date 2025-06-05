<?php

namespace App\Filament\Pages;

use Filament\Tables;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use App\Models\BridgingSuratKontrolBPJS;

class RegistrasiKontrol extends Page implements HasForms, HasTable
{
    use \BezhanSalleh\FilamentShield\Traits\HasPageShield;
    use \Filament\Tables\Concerns\InteractsWithTable;
    use \Filament\Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Notifikasi Pasien';

    protected static string $view = 'filament.pages.registrasi-kontrol';

    public function getWahaSesstionName(): string
    {
        return config('waha.sessions.pendaftaran.name');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $query = \App\Models\BridgingSuratKontrolBPJS::query()
                    ->select(['no_sep', 'no_surat', 'tgl_surat', 'tgl_rencana', 'kd_dokter_bpjs'])
                    ->where('tgl_rencana', '>=', now()->format('Y-m-d'))
                    ->whereDoesntHave('referensiJkn')
                    ->whereHas('sep', function ($query) {
                        $query->where('jnspelayanan', '1');
                    });

                return $query->with([
                    'dokter.spesialis',
                    'sep' => fn ($query) => $query->select(['no_sep', 'nama_pasien', 'nomr', 'nmdpdjp']),
                    'sep.pasien' => fn ($query) => $query->select(['no_rkm_medis', 'nm_pasien', 'no_tlp']),
                ]);
            })
            ->defaultSort('tgl_rencana', 'asc')
            ->selectable()
            ->columns([
                Tables\Columns\TextColumn::make('sep.nama_pasien')
                    ->label('Nama Pasien')
                    ->sortable()
                    ->description(fn($record) => "No.HP : " . $record->sep->pasien->no_tlp ?? '-')
                    ->searchable(),

                Tables\Columns\TextColumn::make('no_sep')
                    ->label('No. SEP')
                    ->sortable()
                    ->badge()
                    ->searchable(),

                Tables\Columns\TextColumn::make('no_surat')
                    ->label('No. Surat')
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->searchable(),

                Tables\Columns\TextColumn::make('sep.nmdpdjp')
                    ->label('Dokter')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('tgl_surat')
                    ->label('Tgl Surat')
                    ->date()
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('tgl_rencana')
                    ->label('Tgl Kontrol')
                    ->date()
                    ->sortable()
                    ->searchable(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('no_rawat')
                    ->label('Kirim Notifikasi')
                    ->action(function (array $data, Collection $records, $table, $livewire) {
                        $records = $livewire->getTable()->getRecords();
                        $selectedRecords = $records->whereIn('no_surat', $livewire->selectedTableRecords);

                        $this->handleNotify($selectedRecords);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Eit, Tunggu Dulu!')
                    ->modalDescription('FYI : Pasien tidak akan menerima notifikasi jika penulisan nomor telepon tidak valid, atau ada karakter yang tidak sesuai.')
                    ->modalSubmitActionLabel('Oke lah!')
                    ->modalIconColor('danger')
                    ->color('success')
                    ->icon('heroicon-o-bell'),
            ]);
    }


    public function generatePreviewMessage(): string
    {
        // Pastikan $records adalah collection
        $records = $this->getTable()->getRecords();
        if ($records instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $records = collect($records->items());
        }

        $randomRecord = $records->isNotEmpty() ? $records->random() : null;

        // Panggil fungsi untuk generate pesan
        return $this->generateNotificationMessage($randomRecord);
    }

    public function handleNotify(Collection $records): void
    {
        
        $latTime = now()->addSeconds(rand(25, 35));
        foreach ($records as $record) {
            // Panggil fungsi untuk generate pesan
            $message = $this->generateNotificationMessage($record);
            
            // Kirim pesan ke WhatsApp
            \App\Jobs\SendWhatsApp::dispatch($message, $record?->sep?->pasien?->no_tlp, $this->getWahaSesstionName())
                ->delay($latTime)
                ->onQueue('whatsapp');

            // Kirim pesan ke Telegram
            $latTime = $latTime->addSeconds(rand(10, 25));
        }

        \Filament\Notifications\Notification::make()
            ->title('Notifikasi Terkirim')
            ->body('Pesan notifikasi berhasil dikirim.')
            ->icon('heroicon-o-check-circle')
            ->iconColor('success')
            ->success()
            ->send();
    }

    private function generateNotificationMessage(null|BridgingSuratKontrolBPJS $record): string
    {
        $nama = $record?->sep?->nama_pasien ?? '[ NAMA PASIEN ]';
        $dokter = $record?->sep?->nmdpdjp ?? '[ NAMA DOKTER ]';
        $tglRencana = Carbon::parse($record?->tgl_rencana)->translatedFormat('l, d F Y') ?? '[ TANGGAL KONTROL ]';
        $spesialis = $record?->dokter?->spesialis?->nm_sps ?? '[ POLIKLINIK ]';

        // if spesialis contain "spesialis" in all case, then remove it
        if ($spesialis != '[ POLIKLINIK ]') {
            $spesialis = Str::title(Str::replace('spesialis', '', Str::lower($spesialis)));
        }

        // ----------

        $text = "Assalamualaikum wr. wb." . "<br />";
        $text .= "RSIA AISYIYAH PEKAJANGAN" . "<br /><br />";

        $text .= "Selamat " . \App\Helpers\TimeHelper::getState(Carbon::now()->translatedFormat("H:i")) . " Bapak/Ibu {$nama} 🙏😊"  . "<br />";
        $text .= "Mengingatkan untuk jadwal kontrol anda : "  . "<br /><br />";

        $text .= "🗓 <b>Tanggal</b> : {$tglRencana}" . "<br />";
        // $text .= "⌛ <b>Waktu</b> : " . Carbon::parse($jam->jam_mulai)->translatedFormat('H:i') . " WIB - Selesai" . "<br />";
        $text .= "🏥 <b>Poliklinik</b> : Poliklinik {$spesialis}" . "<br />";
        $text .= "🩺 <b>Dokter</b> : {$dokter}" . "<br /><br />";

        $text .= "Apakah sudah melakukan pendaftaran untuk kontrol melalui <b>aplikasi Mobile JKN</b> ?" . "<br />";
        $text .= "Mohon konfirmasi Bapak/Ibu 🙏" . "<br /><br />";

        $text .= "Apabila ada kendala saat mendaftar bapak/ibu bisa hubungi kami kembali." . "<br /><br />";

        $text .= "Terima kasih 🙏" . "<br />";
        $text .= "Sehat dan Bahagia bersama kami! 😊";

        return $text;
    }

    // TODO : Ambil data pasien yang memilki surat kontrol tapi belum melakukan registrasi periksa.
}
