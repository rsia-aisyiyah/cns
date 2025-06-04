<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\UserResource\Pages;

class UserResource extends Resource
{
    protected static ?string $model = \App\Models\User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationGroup = 'Pelindung';

    public static function canCreate(): bool
    {
        // return static::can('create');
        return false; // Disable create action for all users and roles
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->select([
                DB::raw("AES_DECRYPT(user.id_user, '" . env('MYSQL_AES_KEY_IDUSER') . "') AS id_user"),
                'pegawai.*'
            ])
            ->leftJoin('pegawai', DB::raw("AES_DECRYPT(user.id_user, '" . env('MYSQL_AES_KEY_IDUSER') . "')"), '=', 'pegawai.nik')
            ->with(['roles']); // 'detail' tidak usah, karena sudah di-join
    }


    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->columns([
                Tables\Columns\TextColumn::make('id_user')->label('User ID')
                    ->searchable(
                        true,
                        fn (Builder $query, string $search) => $query->whereRaw("AES_DECRYPT(user.id_user, '" . env('MYSQL_AES_KEY_IDUSER') . "') LIKE ?", ['%' . $search . '%'])
                    )
                    ->badge()
                    ->color(fn ($state) => preg_match('/^\d\.\d{3}\.\d{4}$/', $state) ? 'primary' : 'danger')
                    ->sortable(),
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'super_admin' => 'danger',
                        'admin' => 'danger',
                        'nomad' => 'warning',
                        default => 'primary',
                    })
                    ->sortable(),
            ])
            ->defaultSort('id_user', 'asc') 
            ->filters([
                // 
            ])
            ->actions([
                // custom action to assign roles with modal
                Tables\Actions\Action::make('assignRoles')
                    ->label('Assign Roles')
                    ->icon('heroicon-o-tag')
                    ->modalHeading('Assign Roles to User')
                    ->form([
                        Forms\Components\Select::make('roles')
                            ->label('Roles')
                            ->autofocus(false)
                            ->multiple()
                            ->options(\App\Models\Role::all()->pluck('name', 'name'))
                            ->searchable()
                            ->default(fn ($record) => $record->roles->pluck('name')->toArray()),
                    ])
                    ->action(function ($record, array $data) {
                        if (empty($data['roles'])) {
                            $record->roles()->detach();
                        } else {
                            $record->syncRoles($data['roles']);
                        }
                    })
                    ->after(function ($record) {
                        \Filament\Notifications\Notification::make()
                            ->title('Roles assigned successfully')
                            ->success()
                            ->send();
                    })
                    ->color('primary'),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
