<?php

namespace App\Filament\Resources\YoutubeAccountResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
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

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
