<?php

namespace App\Filament\Pages;

use Filament\Forms\Form;
use Filament\Pages\Page;
use App\Models\RegPeriksa;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;

class DokterOff extends Page implements HasForms, HasTable
{
    use \BezhanSalleh\FilamentShield\Traits\HasPageShield;
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-minus';

    protected static string $view = 'filament.pages.dokter-off';

    protected static ?string $navigationGroup = 'Notifikasi Pasien';

    // PROPERTIES untuk filter form
    public ?string $tgl_registrasi = null;
    public ?string $kd_poli = null;
    public ?string $kd_dokter = null;

    // PROPERTIES untuk queue form
    public ?string $q_tgl_praktik = null;
    public ?string $q_kd_poli = null;

    public ?Collection $q_kd_poli_options = null;

    public function mount(): void
    {
        $this->tgl_registrasi = now()->format('Y-m-d');
        $this->q_tgl_praktik = $this->tgl_registrasi;

        $this->setPoliOptions();
    }

    public function getWahaSesstionName(): string
    {
        return config('waha.sessions.byu-ferry.name');
    }

    public function getForms(): array
    {
        return [
            'filterForm',
            'queueForm',
        ];
    }

    protected function filterForm(Form $form)
    {
        return $form
            ->schema([
                Section::make('Filter Data Pasien')
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
                            ->afterStateUpdated(function () {
                                $this->resetTable();
                                $this->setPoliOptions();
                            }),

                        Select::make('kd_poli')
                            ->label('Poliklinik')
                            ->options(\App\Models\Poliklinik::whereHas("jadwal_dokter")->pluck('nm_poli', 'kd_poli'))
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(function () {
                                $this->resetTable();
                            }),
                    ]),
            ]);
    }

    protected function queueForm(Form $form)
    {
        return $form
            ->schema([
                Section::make("Jadwal Praktik Dokter")
                    ->columns(1)
                    ->reactive()
                    ->schema([
                        DatePicker::make('q_tgl_praktik')
                            ->label('Tanggal')
                            ->placeholder('Pilih Tanggal Praktik')
                            ->native(false)
                            ->reactive()
                            ->afterStateUpdated(function ($state) {
                                $this->setPoliOptions();
                            }),

                        // select poliklinik
                        Select::make('q_kd_poli')
                            ->label('Poliklinik')
                            ->options(fn() => $this->q_kd_poli_options ?? collect())
                            ->hidden(fn() => $this->q_kd_poli_options?->isEmpty())
                            ->searchable()
                            ->reactive()
                            ->live()
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

                if ($this->kd_poli) {
                    $query->where('kd_poli', $this->kd_poli);
                }

                if ($this->kd_dokter) {
                    $query->where('kd_dokter', $this->kd_dokter);
                }

                return $query->with(['dokter', 'pasien', 'poli', 'jadwal_dokter'])->whereHas('jadwal_dokter');
            })
            ->defaultSort('no_rawat', 'desc')
            ->selectable()
            ->columns([
                TextColumn::make('no_rawat')
                    ->label('No. Rawat')
                    ->description(fn($record) => $record->no_rkm_medis)
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

    public function handleNotify(Collection $records): void
    {
        $latTime = now()->addSeconds(rand(25, 35));
        foreach ($records as $record) {
            // Panggil fungsi untuk generate pesan
            $message = $this->generateNotificationMessage($record);

            // Kirim pesan ke WhatsApp menggunakan job
            \App\Jobs\SendWhatsApp::dispatch($message, $record->pasien->no_tlp, $this->getWahaSesstionName())
                ->delay($latTime)
                ->onQueue('whatsapp');

            $latTime = $latTime->addSeconds(rand(25, 35));
        }

        \Filament\Notifications\Notification::make()
            ->title('Notifikasi Terkirim')
            ->body('Pesan notifikasi berhasil dikirim.')
            ->icon('heroicon-o-check-circle')
            ->iconColor('success')
            ->success()
            ->send();
    }

    public function generatePreviewMessage(): string
    {
        $randomRecord = null;
        $selectedRecords = $this->getTable()->getLivewire()->getAllSelectableTableRecordKeys();
        if (count($selectedRecords) > 0) {
            // Pilih nomor rawat secara acak dari yang terpilih
            $noRawat = $selectedRecords[array_rand($selectedRecords)];

            $records = $this->getTable()->getRecords();
            $randomRecord = $records->where('no_rawat', $noRawat)->first();
        }

        return $this->generateNotificationMessage($randomRecord);
    }

    private function generateNotificationMessage(?RegPeriksa $record = null): string
    {
        $hariRegistrasi = $this->tgl_registrasi
            ? \Carbon\Carbon::parse($this->tgl_registrasi)->translatedFormat('l, d F Y')
            : 'ini';

        $hariPraktik = $this->q_tgl_praktik
            ? \Carbon\Carbon::parse($this->q_tgl_praktik)->translatedFormat('l, d F Y')
            : 'ini';

        $hariKerja = Str::upper(\Illuminate\Support\Carbon::parse($this->q_tgl_praktik)->translatedFormat('l'));
        $poliBaru = $this->q_kd_poli
            ? \App\Models\JadwalPoliklinik::where('kd_poli', $this->q_kd_poli)
                ->whereDate('hari_kerja', $hariKerja)
                ->where('kd_dokter', $this->kd_dokter)
                ->first()
            : null;

        // Gunakan data default jika tidak ada record (untuk preview)
        $namaDokter = $record?->dokter?->nm_dokter ?? 'Nama Dokter';
        $jamMulaiPoli = $record?->jadwal_dokter?->jam_mulai ?? null;
        $jamSelesaiPoli = $record?->jadwal_dokter?->jam_selesai ?? null;

        $text = '';
        $text .= 'Assalamualaikum wr. wb.' . '<br />';
        $text .= 'Selamat siang Bapak/Ibu 🙏😊' . '<br /><br />';

        $text .= "Kepada pasien <i><b>{$namaDokter}</b></i>, hari {$hariRegistrasi}, poliklinik {$namaDokter} (" . ($jamMulaiPoli && $jamSelesaiPoli ? \Carbon\Carbon::parse($jamMulaiPoli)->translatedFormat('H:i') . " - " . \Carbon\Carbon::parse($jamSelesaiPoli)->translatedFormat('H:i') : '') . ") <strong>TUTUP PRAKTIK</strong>." . '<br />';
        $text .= 'Pasien dapat mengatur ulang jadwal periksa' . (
            $this->q_tgl_praktik && $poliBaru
            ? " pada hari {$hariPraktik}" . ($poliBaru ? ' (' . \Carbon\Carbon::parse($poliBaru->jam_mulai)->translatedFormat('H:i') . " - " . \Carbon\Carbon::parse($poliBaru->jam_selesai)->translatedFormat('H:i') . ')' : '.') . '.'
            : ' di hari lain.'
        ) . '<br /><br />';

        $text .= 'Kami sangat menghargai jika Bapak/Ibu dapat memberikan konfirmasi penerimaan informasi ini.' . '<br />';
        $text .= 'Terima kasih atas perhatian dan pengertiannya 🙏' . '<br /><br />';

        $text .= '<b>RSIA AISYIYAH PEKAJANGAN</b>' . '<br />-----<br />';
        $text .= 'Pesan ini dikirim otomatis, pertanyaan dan informasi dapat disampaikan ke nomor 085640009934';

        return $text;
    }

    protected function setPoliOptions(): void
    {
        $hariKerja = Str::upper(\Illuminate\Support\Carbon::parse($this->q_tgl_praktik)->translatedFormat('l'));
        $this->q_kd_poli_options = \App\Models\JadwalPoliklinik::with('poliklinik')
            ->where('hari_kerja', $hariKerja)
            ->where('kd_dokter', $this->kd_dokter)
            ->get()
            ->mapWithKeys(fn($item) => [
                $item->kd_poli => optional($item->poliklinik)->nm_poli
                    . ' (' . \Carbon\Carbon::parse($item->jam_mulai)->translatedFormat('H:i')
                    . ' - ' . \Carbon\Carbon::parse($item->jam_selesai)->translatedFormat('H:i') . ')',
            ]);
    }
}
