<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\AccountStatusResource\Pages\ListAccountStatuses;
use App\Filament\Resources\AccountStatusResource\Pages\CreateAccountStatus;
use App\Filament\Resources\AccountStatusResource\Pages\EditAccountStatus;
use App\Filament\Resources\AccountStatusResource\Pages;
use App\Filament\Resources\AccountStatusResource\RelationManagers;
use App\Models\AccountStatus;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput; # Agregar si es un Input [Form]
use Filament\Tables\Columns\TextColumn; # Agregar si es un Column [Table]
use Filament\Forms\Components\Textarea;

class AccountStatusResource extends Resource
{
    protected static ?string $model = AccountStatus::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string | \UnitEnum | null $navigationGroup = 'Configuracion';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->extraAttributes(['class' => 'mi-clase-personalizada'])
            ->components([
                TextInput::make('name')->required(),
                Textarea::make('description'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('description')->limit(50),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
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
            'index' => ListAccountStatuses::route('/'),
            'create' => CreateAccountStatus::route('/create'),
            'edit' => EditAccountStatus::route('/{record}/edit'),
        ];
    }
}
