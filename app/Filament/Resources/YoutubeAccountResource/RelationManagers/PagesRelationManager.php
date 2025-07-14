<?php

namespace App\Filament\Resources\YoutubeAccountResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use App\Models\YoutubePage;
use Filament\Tables\Columns\TextColumn;

class PagesRelationManager extends RelationManager
{
    protected static string $relationship = 'pages';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('youtube_page_id')
                    ->label('Página Registrada')
                    ->options(YoutubePage::query()->pluck('name', 'id')->toArray()) // Lista las páginas disponibles
                    ->searchable()
                    ->required(),

                TextInput::make('email')
                    ->label('Correo Usado'),

                TextInput::make('password')
                    ->label('Contraseña'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('page_name')
            ->columns([
                TextColumn::make('page.name')->label('Página Registrada'),
                TextColumn::make('email')->label('Correo Usado'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
