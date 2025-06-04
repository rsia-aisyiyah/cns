<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Pegawai;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\PegawaiResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\PegawaiResource\RelationManagers;

class PegawaiResource extends Resource
{
    protected static ?string $model = Pegawai::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Pegawai';

    protected static ?string $label = 'Data Pegawai';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(Pegawai::query()->where('stts_aktif', '=', 'AKTIF'))
            ->defaultSort('nik', 'asc')
            ->recordUrl(null)
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
                    ->description( fn ($record) => 'DEP : ' . Str::upper($record->unit->nama ?? '-'))
                    ->sortable()
                    ->searchable(true, fn (Builder $query, $search) => $query
                        ->where('jbtn', 'like', "%{$search}%")
                        ->orWhereHas('unit', function (Builder $query) use ($search) {
                            $query->where('nama', 'like', "%{$search}%");
                        })
                    ),
                Tables\Columns\TextColumn::make('mulai_kerja')
                    ->label('Mulai Kerja')
                    ->date('l, d M Y')
                    ->description(fn ($record) => 
                        ($record->mulai_kerja
                            ? \Carbon\Carbon::parse($record->mulai_kerja)->diff(\Carbon\Carbon::now())->format('%y th %m Bl %d Hr')
                            : '-'
                        )
                    )
                    ->sortable()
                    ->searchable(),
            ])
            ->actions([
                // 
            ])
            ->bulkActions([
                // 
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPegawais::route('/'),
            'create' => Pages\CreatePegawai::route('/create'),
            'edit' => Pages\EditPegawai::route('/{record}/edit'),
        ];
    }
}
