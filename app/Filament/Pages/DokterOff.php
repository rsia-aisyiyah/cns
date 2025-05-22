<?php

namespace App\Filament\Pages;

use Filament\Forms\Form;
use Filament\Pages\Page;
use App\Models\RegPeriksa;
use Filament\Tables\Table;
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
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-minus';

    protected static string $view = 'filament.pages.dokter-off';

    protected static ?string $navigationGroup = 'Notifikasi Pasien';

    // PROPERTIES untuk filter form
    public ?string $tgl_registrasi = null;
    public ?string $kd_poli = null;

    // PROPERTIES untuk queue form
    public ?string $tgl_praktik = null;

    public function mount(): void
    {
        $this->tgl_registrasi = now()->format('Y-m-d');
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
                Section::make()
                    ->columns(2)
                    ->schema([
                        DatePicker::make('tgl_registrasi')
                            ->label('Tgl Registrasi')
                            ->placeholder('Pilih Tanggal Registrasi')
                            ->reactive()
                            ->native(false)
                            ->default(\Illuminate\Support\Carbon::now())
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

    protected function queueForm(Form $form)
    {
        return $form
            ->schema([
                Section::make("Jadwal Praktik")
                    ->columns(1)
                    ->schema([
                        DatePicker::make('tgl_praktik')
                            ->label('Tgl Praktik')
                            ->placeholder('Pilih Tanggal Praktik')
                            ->native(false)
                            ->reactive(),
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

                return $query->with(['poli', 'dokter', 'pasien']);
            })
            ->defaultSort('no_rawat', 'desc')
            ->selectable()
            ->columns([
                TextColumn::make('no_rawat')
                    ->label('No. Rawat')
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
                    ->description(fn($record) => $record->pasien->no_rkm_medis)
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
            \App\Jobs\SendWhatsApp::dispatch($message, $record->pasien->no_tlp)
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

        $hariPraktik = $this->tgl_praktik
            ? \Carbon\Carbon::parse($this->tgl_praktik)->translatedFormat('l, d F Y')
            : 'ini';

        // Gunakan data default jika tidak ada record (untuk preview)
        $namaDokter = $record?->dokter?->nm_dokter ?? 'Nama Dokter';
        $namaPoli   = $record?->poli?->nm_poli ?? 'Nama Poliklinik';

        $text = '';
        $text .= 'Assalamualaikum wr. wb.' . '<br />';
        $text .= 'Selamat siang Bapak/Ibu 🙏😊' . '<br /><br />';

        $text .= "Kepada pasien <i><b>{$namaDokter}</b></i>, hari {$hariRegistrasi}, poliklinik {$namaPoli} {$namaDokter} <strong>TUTUP PRAKTIK</strong>." . '<br />';
        $text .= 'Pasien dapat mengatur ulang jadwal periksa' . (
            $this->tgl_praktik 
                ? " pada hari {$hariPraktik}." 
                : ' di hari lain.'
            ) . '<br /><br />';

        $text .= 'Kami sangat menghargai jika Bapak/Ibu dapat memberikan konfirmasi penerimaan informasi ini.' . '<br />';
        $text .= 'Terima kasih atas perhatian dan pengertiannya 🙏' . '<br /><br />';

        $text .= '<i><b>RSIA AISYIYAH PEKAJANGAN</b></i>';

        return $text;
    }
}
