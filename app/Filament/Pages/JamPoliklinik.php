<?php

namespace App\Filament\Pages;

use Filament\Forms\Form;
use Filament\Pages\Page;
use App\Models\RegPeriksa;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Tables\Columns\CheckboxColumn;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;

class JamPoliklinik extends Page implements HasForms, HasTable
{
    use \BezhanSalleh\FilamentShield\Traits\HasPageShield;
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static string $view = 'filament.pages.jam-poliklinik';

    protected static ?string $navigationGroup = 'Notifikasi Pasien';

    // PROPERTIES untuk filter form
    public ?string $tgl_registrasi = null;
    public ?string $kd_dokter = null;
    public ?string $kd_poli = null;

    // PROPERTIES untuk queue form (input jam)
    public ?string $jam_mulai = null;
    public ?string $jam_selesai = null;
    public bool $disableJamSelesai = true;

    public array $tableSelection = [];

    public function mount(): void
    {
        // set jam_mulai dan jam_selesai default
        $this->jam_mulai = now()->format('H:i');
        $this->disableJamSelesai = true;

        // set tgl_registrasi default
        $this->tgl_registrasi = now()->format('Y-m-d');
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

    // Form queueing data tambahan
    protected function queueForm(Form $form)
    {
        return $form
            ->schema([
                Section::make('Jam Praktik Baru')
                    ->columns(1)
                    ->schema([
                        Grid::make(2)->schema([
                            TimePicker::make('jam_mulai')
                                ->label('Jam Mulai')
                                ->reactive()
                                ->default("16:00")
                                ->seconds(false),

                            TimePicker::make('jam_selesai')
                                ->label('Jam Selesai')
                                ->reactive()
                                ->disabled(fn(callable $get) => $this->disableJamSelesai)
                                ->default("16:00")
                                ->seconds(false),
                        ]),

                        // Checkbox disable jam_selesai
                        \Filament\Forms\Components\Checkbox::make('disableJamSelesai')
                            ->label('Tidak Ditentukan')
                            ->reactive()
                            ->default(true),
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

                return $query->with(['poli', 'dokter', 'pasien'])->whereHas('jadwal_dokter');
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
        // Ambil state dari form queue (input jam)
        $queueFormState = $this->queueForm->getState();
        $jamMulai = $queueFormState['jam_mulai'];
        $jamSelesai = $queueFormState['disableJamSelesai'] ? 'Selesai' : $queueFormState['jam_selesai'];

        // Pastikan $records adalah collection
        $records = $this->getTable()->getRecords();
        if ($records instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $records = collect($records->items());
        }

        $randomRecord = $records->isNotEmpty() ? $records->random() : null;
        // $nama = $randomRecord?->pasien?->nm_pasien ?? '[ NAMA PASIEN ]';

        // Panggil fungsi untuk generate pesan
        return $this->generateNotificationMessage($randomRecord, $jamMulai, $jamSelesai);
    }

    public function handleNotify(Collection $records): void
    {
        $queueFormState = $this->queueForm->getState();
        $jamMulai = $queueFormState['jam_mulai'];
        $jamSelesai = $queueFormState['disableJamSelesai'] ?? false ? 'Selesai' : $queueFormState['jam_selesai'];

        $latTime = now()->addSeconds(rand(25, 35));
        foreach ($records as $record) {
            // Panggil fungsi untuk generate pesan
            $message = $this->generateNotificationMessage($record, $jamMulai, $jamSelesai);

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

    private function generateNotificationMessage(null|RegPeriksa $record, string $jamMulai, string|null $jamSelesai): string
    {
        $nama = $record;
        $dokter = '[ NAMA DOKTER ]';
        if ($record instanceof RegPeriksa) {
            $nama = $record?->pasien?->nm_pasien ?? '[ NAMA PASIEN ]';
            $dokter = $record?->dokter?->nm_dokter ?? '[ NAMA DOKTER ]';
        }

        // Format tanggal registrasi
        $tglRegistrasi = \Carbon\Carbon::parse($this->tgl_registrasi)->translatedFormat('l, d F Y');

        // Jadwal
        $jadwal = null;
        if ($this->kd_dokter && $this->kd_poli) {
            $jadwal = \App\Models\JadwalPoliklinik::where('kd_dokter', $this->kd_dokter)
                ->where('kd_poli', $this->kd_poli)
                ->first();
        } else {
            if ($record instanceof RegPeriksa) {
                $jadwal = \App\Models\JadwalPoliklinik::where('kd_dokter', $record->kd_dokter)
                    ->where('kd_poli', $record->kd_poli)
                    ->first();
            }
        }

        // Menentukan jadwal
        $jadwalStart = $jadwal ? $jadwal->jam_mulai : null;
        $jadwalEnd = $jadwal ? $jadwal->jam_selesai : null;

        // Pesan pemberitahuan
        $jadwalText = '';
        if ($jadwalStart && $jadwalEnd) {
            $start = \Illuminate\Support\Carbon::createFromFormat('H:i:s', $jadwalStart)->format('H:i');
            $end = \Illuminate\Support\Carbon::createFromFormat('H:i:s', $jadwalEnd)->format('H:i');

            $jadwalText = " yang semula jam praktik <span class='px-2 text-nowrap bg-amber-200 dark:bg-amber-600 rounded'><strong class='font-semibold'>{$start} s/d {$end}</strong></span>";
        }

        $html = '';
        $html .= 'Yth. Bpk/Ibu :<br />';
        $html .= '<strong>' . $nama . '</strong><br /><br />';
        $html .= 'Kami informasikan adanya perubahan jam praktik untuk dokter <strong>' . $dokter . '</strong> pada <strong>' . $tglRegistrasi . '</strong>' . ' menjadi jam <span class="px-2 text-nowrap bg-emerald-200 dark:bg-emerald-600 rounded"><strong class="font-semibold">' . $jamMulai . ' s/d ' . ($jamSelesai ?? 'selesai') . '</strong></span>.<br /><br />';
        $html .= 'Mohon maaf atas ketidaknyamanan 🙏🏻🙏🏻.<br />';
        $html .= 'Terima kasih atas perhatian dan kerjasamanya.' . '<br /><br />';
        $html .= '<b>RSIA AISYIYAH PEKAJANGAN</b>' . '<br />-----<br />';
        $html .= 'Pesan ini dikirim otomatis, pertanyaan dan informasi dapat disampaikan ke nomor 085640009934';

        return $html;
    }
}
