<?php

namespace App\Filament\Pages;

use Filament\Forms\Form;
use Filament\Pages\Page;
use App\Models\RegPeriksa;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\DatePicker;

class KonfirmasiHadir extends Page implements HasForms, HasTable
{
    use \BezhanSalleh\FilamentShield\Traits\HasPageShield;
    use \Filament\Tables\Concerns\InteractsWithTable;
    use \Filament\Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    protected static ?string $navigationGroup = 'Notifikasi Pasien';

    protected static string $view = 'filament.pages.konfirmasi-hadir';

    // PROPERTIES untuk filter form
    public ?string $tgl_registrasi = null;
    public ?string $kd_dokter = null;
    public ?string $kd_poli = null;

    public function mount(): void
    {
        // set tgl_registrasi default
        $this->tgl_registrasi = now()->format('Y-m-d');
    }

    public function getWahaSesstionName(): string
    {
        return config('waha.sessions.pendaftaran.name');
    }

    public function getForms(): array
    {
        return [
            'filterForm'
        ];
    }

    protected function filterForm(Form $form)
    {
        return $form
            ->schema([
                Section::make()
                    ->columns(3)
                    ->schema([
                        DatePicker::make('tgl_registrasi')
                            ->label('Tgl Registrasi')
                            ->placeholder('Pilih Tanggal Registrasi')
                            ->reactive()
                            ->native(false)
                            ->default(\Illuminate\Support\Carbon::now())
                            ->afterStateUpdated(fn() => $this->resetTable()),

                        Select::make('kd_dokter')
                            ->label('Dokter')
                            ->options(\App\Models\Dokter::whereHas("jadwal")->pluck('nm_dokter', 'kd_dokter'))
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(fn() => $this->resetTable()),

                        Select::make('kd_poli')
                            ->label('Poliklinik')
                            ->options(\App\Models\Poliklinik::whereHas("jadwal_dokter")->pluck('nm_poli', 'kd_poli'))
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(fn() => $this->resetTable()),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $query = RegPeriksa::query();

                if ($this->tgl_registrasi) {
                    $query->whereDate('tgl_registrasi', $this->tgl_registrasi);
                } else {
                    $query->whereDate('tgl_registrasi', now()->format('Y-m-d'));
                }

                if ($this->kd_dokter) {
                    $query->where('kd_dokter', $this->kd_dokter);
                }

                if ($this->kd_poli) {
                    $query->where('kd_poli', $this->kd_poli);
                }

                return $query->with(['poli', 'dokter', 'pasien', 'jadwal_dokter'])->whereHas('jadwal_dokter');
            })
            ->defaultSort('no_rawat', 'desc')
            ->selectable()
            ->columns([
                TextColumn::make('no_rawat')
                    ->label('No. Rawat')
                    ->description(fn($record) => $record->pasien->no_rkm_medis)
                    ->searchable(),

                TextColumn::make('tgl_registrasi')
                    ->label('Tgl. Registrasi')
                    ->formatStateUsing(fn($state) => \Carbon\Carbon::parse($state)->translatedFormat('d F Y'))
                    ->description(fn($record) => $record->jam_reg)
                    ->searchable(),

                TextColumn::make('poli.nm_poli')
                    ->label('Nama Poliklinik')
                    ->description(fn($record) => $record->dokter->nm_dokter)
                    ->searchable(),

                TextColumn::make('pasien.nm_pasien')
                    ->label('Nama Pasien')
                    ->description(fn($record) => preg_match('/^\+?\d{10,15}$/', $record->pasien->no_tlp) ? $record->pasien->no_tlp : new \Illuminate\Support\HtmlString('<span class="text-amber-500 font-semibold">' . $record->pasien->no_tlp . '</span>'))
                    ->searchable(),
            ])
            ->bulkActions([
                BulkAction::make('no_rawat')
                    ->label('Kirim Notifikasi')
                    ->action(function (array $data, Collection $records, $table, $livewire) {
                        $records = $livewire->getTable()->getRecords();
                        $selectedRecords = $records->whereIn('no_rawat', $livewire->selectedTableRecords);

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
            \App\Jobs\SendWhatsApp::dispatch($message, $record->pasien->no_tlp, $this->getWahaSesstionName())
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

    private function generateNotificationMessage(null|RegPeriksa $record): string
    {
        $nama = $record?->pasien?->nm_pasien ?? '[ NAMA PASIEN ]';
        $dokter = $record?->dokter?->nm_dokter ?? '[ NAMA DOKTER ]';

        // ----------

        $text = "Assalamualaikum wr. wb." . "<br />";
        $text .= "RSIA AISYIYAH PEKAJANGAN" . "<br /><br />";

        $text .= "Selamat ". \App\Helpers\TimeHelper::getState(Carbon::now()->translatedFormat("H:i")) ." Ibu {$nama} 🙏😊"  . "<br /><br />";

        $text .= "Mohon izin menginformasikan untuk poli <b>{$record?->poli?->nm_poli}</b> <b>{$dokter}</b> untuk hari ini sudah dimulai." . "<br />";
        $text .= "dimohon segera datang 🙏" . "<br /><br />";

        $text .= "Apakah pasien hadir periksa untuk hari ini ?"  . "<br />";
        $text .= "Kami tunggu balasan dari pasien" . "<br />";
        $text .= "Apabila pasien berhalangan hadir dimohon untuk mengkonfirmasi kami." . "<br /><br />";

        $text .= "Terima kasih atas perhatian dan kerjasamanya 🙏" . "<br /><br />";

        $text .= "Sehat dan Bahagia bersama kami! 😊";

        return $text;
    }
}
