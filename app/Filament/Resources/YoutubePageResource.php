<?php

namespace App\Filament\Resources;

use App\Filament\Resources\YoutubePageResource\Pages;
use App\Filament\Resources\YoutubePageResource\RelationManagers;
use App\Models\YoutubePage;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\RichEditor;

class YoutubePageResource extends Resource
{
    protected static ?string $model = YoutubePage::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Configuracion';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nombre de la Página')
                    ->required(),
                TextInput::make('url')
                    ->label('URL de la Página')
                    ->url()
                    ->required(),
                RichEditor::make('descripcion')
                    ->label('Descripción')
                    ->nullable()
                    ->toolbarButtons([
                        'attachFiles',
                        'blockquote',
                        'bold',
                        'bulletList',
                        'codeBlock',
                        'h2',
                        'h3',
                        'italic',
                        'link',
                        'orderedList',
                        'redo',
                        'strike',
                        'undo',
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nombre de la Página'),
                TextColumn::make('url')->label('URL de la Página'),
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
            'index' => Pages\ListYoutubePages::route('/'),
            'create' => Pages\CreateYoutubePage::route('/create'),
            'edit' => Pages\EditYoutubePage::route('/{record}/edit'),
        ];
    }
}
