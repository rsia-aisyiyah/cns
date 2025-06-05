<?php

namespace App\Filament\Resources\PasienResource\Pages;

use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\PasienResource;

class EditPasien extends EditRecord
{
    protected static string $resource = PasienResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Data Pasien')
                    ->schema([
                        Forms\Components\Grid::make([
                            'default' => 1,
                            'sm' => 2,
                        ])->schema([
                            Forms\Components\TextInput::make('no_rkm_medis')
                                ->label('No. RM')
                                ->required()
                                ->maxLength(20)
                                ->unique(ignoreRecord: true),

                            Forms\Components\TextInput::make('no_ktp')
                                ->label('No. KTP')
                                ->required()
                                ->maxLength(20),
                        ]),

                        Forms\Components\TextInput::make('nm_pasien')
                            ->label('Nama Pasien')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\Grid::make([
                            'default' => 1,
                            'sm' => 2,
                            'md' => 3,
                        ])->schema([
                            Forms\Components\TextInput::make('tmp_lahir')
                                ->label('Tempat Lahir')
                                ->required()
                                ->columnSpan([
                                    'sm' => 2,
                                    'md' => 1,
                                ])
                                ->maxLength(50),

                            Forms\Components\DatePicker::make('tgl_lahir')
                                ->label('Tanggal Lahir')
                                ->native(false)
                                ->required(),

                            Forms\Components\Select::make('jk')
                                ->label('Jenis Kelamin')
                                ->options([
                                    'L' => 'Laki-laki',
                                    'P' => 'Perempuan',
                                ])
                                ->required(),
                        ]),

                        Forms\Components\TextInput::make('nm_ibu')
                            ->label('Nama Ibu')
                            ->required(),

                        Forms\Components\Textarea::make('alamat')
                            ->label('Alamat')
                            ->required()
                            ->maxLength(255),

                        // grid 5 columns
                        Forms\Components\Grid::make([
                            'default' => 1,
                            'sm' => 2,
                            'md' => 3,
                            'lg' => 4,
                        ])->schema([
                            // gol_darah, pekerjaa, stts_nikah, agama, no_tlp
                            Forms\Components\Select::make('gol_darah')
                                ->label('Golongan Darah')
                                ->options([
                                    'A' => 'A',
                                    'B' => 'B',
                                    'AB' => 'AB',
                                    'O' => 'O',
                                    '-' => '-',
                                ])
                                ->required(),

                            Forms\Components\Select::make('stts_nikah')
                                ->label('Status Pernikahan')
                                ->options([
                                    'BELUM MENIKAH' => 'Belum Menikah',
                                    'MENIKAH' => 'Menikah',
                                    'JANDA' => 'Janda',
                                    'DUDHA' => 'Duda',
                                    'JOMBLO' => 'Jomblo',
                                ])
                                ->required(),

                            Forms\Components\Select::make('agama')
                                ->label('Agama')
                                ->options([
                                    'ISLAM' => 'Islam',
                                    'KRISTEN' => 'Kristen',
                                    'KATOLIK' => 'Katolik',
                                    'HINDU' => 'Hindu',
                                    'BUDDHA' => 'Buddha',
                                    'KONGHUCU' => 'Konghucu',
                                    'LAINNYA' => 'Lainnya',
                                ])
                                ->required(),

                            Forms\Components\TextInput::make('pekerjaan')
                                ->label('Pekerjaan')
                                ->required()
                                ->columnSpan([
                                    'md' => 3,
                                    'lg' => 1,
                                ])
                                ->maxLength(50),
                        ]),


                        // grid 5 columns
                        Forms\Components\Grid::make([
                            'default' => 1,
                            'sm' => 2,
                        ])->schema([
                            Forms\Components\TextInput::make('no_tlp')
                                ->label('No. Telepon')
                                ->required()
                                ->maxLength(15),

                            Forms\Components\TextInput::make('no_peserta')
                                ->label('No. Peserta')
                                ->required()
                                ->maxLength(20)
                        ])
                    ]),
            ]);
    }
}
