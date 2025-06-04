<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Tables;
use App\Models\Pegawai;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;

class UndanganPegawai extends Page implements HasForms, HasTable
{
    use \BezhanSalleh\FilamentShield\Traits\HasPageShield;
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static string $view = 'filament.pages.undangan-pegawai';

    protected static ?string $navigationGroup = 'Notifikasi Internal';

    public ?string $undanganId = null;
    public ?string $tipe = null;

    public $undangan = null;

    public bool $asImage = false;
    public bool $isImage = false;
    public $file = [];

    public $keterangan = null;

    public function mount(): void
    {
        // Inisialisasi jika undanganId sudah ada
        if ($this->undanganId) {
            $this->undangan = \App\Models\Undangan::find($this->undanganId);
            if ($this->undangan) {
                $this->tipe = $this->undangan->tipe;
            }
        }

        $this->keterangan = 'Terima kasih 🙏🏻🙏🏻';
    }

    public function getForms(): array
    {
        return [
            'filterForm',
            'queueForm',
        ];
    }

    public function getWahaSesstionName(): string
    {
        return config('waha.sessions.byu-ferry.name');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                if (!$this->undanganId) {
                    // Jika undangan belum dipilih, kembalikan query kosong
                    return Pegawai::query()->whereRaw('1 = 0'); // selalu false
                }

                $query = Pegawai::query();
                $query->where('stts_aktif', '=', 'AKTIF');

                // Ambil semua penerima undangan
                $allPenerima = \App\Models\PenerimaUndangan::where('undangan_id', $this->undanganId)
                    ->pluck('penerima')
                    ->toArray();

                $query->whereIn('nik', $allPenerima);

                return $query;
            })
            ->defaultSort('nik', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('nik')
                    ->label('NIK')
                    ->sortable()
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama Pegawai')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('jbtn')
                    ->label('Jabatan')
                    ->description(fn($record) => 'DEP : ' . Str::upper($record->unit->nama ?? '-'))
                    ->sortable()
                    ->searchable(
                        true,
                        fn(Builder $query, $search) => $query
                            ->where('jbtn', 'like', "%{$search}%")
                            ->orWhereHas('unit', function (Builder $query) use ($search) {
                                $query->where('nama', 'like', "%{$search}%");
                            })
                    ),
                Tables\Columns\TextColumn::make('mulai_kerja')
                    ->label('Mulai Kerja')
                    ->date('l, d M Y')
                    ->description(
                        fn($record) => ($record->mulai_kerja
                            ? \Carbon\Carbon::parse($record->mulai_kerja)->diff(\Carbon\Carbon::now())->format('%y th %m Bl %d Hr')
                            : '-'
                        )
                    )
                    ->sortable(),
            ])
            ->bulkActions([]);
    }

    public function filterForm(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('undanganId')
                            ->label('Undangan')
                            ->options(\App\Models\Undangan::orderBy('tanggal', 'desc')->pluck('perihal', 'id'))
                            ->placeholder('Pilih Undangan')
                            ->reactive()->preload()->native(false)
                            ->searchable()
                            ->columnSpan(2)
                            ->afterStateUpdated(function ($state, callable $set) {
                                $this->undanganId = $state;
                                
                                $undangan = \App\Models\Undangan::find($state);
                                if ($undangan) {
                                    $this->undangan = $undangan;
                                    $set('tipe', $undangan->tipe);
                                }
                            }),

                        Forms\Components\TextInput::make('tipe')
                            ->label('Tipe Surat')
                            ->readonly()
                    ]),
            ]);
    }

    public function queueForm(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->columns(1)
                    ->schema([
                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->rows(3)->maxLength(255)
                            ->placeholder('TTD, Tembusan, dsb.')
                            ->helperText('Keterangan ini akan ditampilkan pada pesan notifikasi.')
                            ->reactive()
                            ->required(),
                    ]),
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
        $nama = $randomRecord?->nama ?? '[ PESERTA KEGIATAN ]';

        $message = $this->generateNotificationMessage(
            $records, $nama
        );

        return $message;
    }

    public function notify(): void
    {
        $q = $this->getTable()->getQuery()->withNoTelp();
        $records = $q->get();
        
        if ($records->isEmpty()) {
            $this->notify('warning', 'Tidak ada pegawai yang terpilih untuk diundang.');
            return;
        }

        $latTime = now()->addSeconds(rand(25, 35));
        foreach ($records as $record) {
            // Kirim notifikasi ke setiap pegawai
            $message = $this->generateNotificationMessage($records, $record->nama);

            \App\Jobs\SendWhatsApp::dispatch($message, $record?->petugas?->no_telp, $this->getWahaSesstionName())
                ->delay($latTime)
                ->onQueue('whatsapp');

            $latTime = $latTime->addSeconds(rand(25, 35));
        }
    }

    public function generateNotificationMessage(Collection $undangan, string $penerima): string
    {
        // Escape perihal dan keterangan
        $keterangan = nl2br(e($this->keterangan ?? ''));

        $message = "<b>Perihal:</b> Undangan<br /><br />";
        $message .= "Assalamu'alaikum Wr. Wb.<br /><br />";
        $message .= "Kepada Yth.<br />";
        $message .= "<b>" . e($penerima) . "</b>, dimohon atas kesediaan kehadirannya pada:<br /><br />";

        if ($this->undangan) {
            $message .= "- <b>Hari/Tanggal</b> : " . $this->undangan->tanggal->translatedFormat('l, d M Y') . "<br />";
            $message .= "- <b>Pukul</b> : " . $this->undangan->tanggal->translatedFormat('H:i') . " WIB s/d selesai" . "<br />";
            $message .= "- <b>Tempat</b> : " . e($this->undangan->lokasi) . "<br />";
            $message .= "- <b>Acara</b> : " . e($this->undangan->perihal) . "<br /><br />";
        } else {
            $message .= "[ DETAIL UNDANGAN ]" . "<br /><br />";
        }

        $message .= "{$keterangan}";

        return $message;
    }
}
