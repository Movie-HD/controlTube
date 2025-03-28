<?php

namespace App\Filament\Resources;

use App\Filament\Resources\YoutubeProxyResource\Pages;
use App\Filament\Resources\YoutubeProxyResource\RelationManagers;
use App\Models\YoutubeProxy;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\YoutubeAccount;

class YoutubeProxyResource extends Resource
{
    protected static ?string $model = YoutubeProxy::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Configuracion';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('proxy')
                ->label('Proxy Address')
                ->required(),
                Forms\Components\Toggle::make('in_use')
                    ->label('En Uso'),
                Forms\Components\Select::make('used_by_account_id')
                    ->label('Cuenta de YouTube')
                    ->relationship('usedByAccount', 'email') // Usa relación en vez de pluck()
                    ->searchable()
                    ->preload()
                    ->nullable(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('proxy')
                    ->label('Proxy')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListYoutubeProxies::route('/'),
            'create' => Pages\CreateYoutubeProxy::route('/create'),
            'edit' => Pages\EditYoutubeProxy::route('/{record}/edit'),
        ];
    }
}
