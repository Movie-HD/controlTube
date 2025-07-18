<?php

namespace App\Filament\Resources\ServerMovies;

use App\Filament\Resources\ServerMovies\Pages\CreateServerMovie;
use App\Filament\Resources\ServerMovies\Pages\EditServerMovie;
use App\Filament\Resources\ServerMovies\Pages\ListServerMovies;
use App\Filament\Resources\ServerMovies\Schemas\ServerMovieForm;
use App\Filament\Resources\ServerMovies\Tables\ServerMoviesTable;
use App\Models\ServerMovie;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ServerMovieResource extends Resource
{
    protected static ?string $model = ServerMovie::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ServerMovieForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ServerMoviesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\ServerMovies\RelationManagers\MovieLinkHistoriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServerMovies::route('/'),
            'create' => CreateServerMovie::route('/create'),
            'edit' => EditServerMovie::route('/{record}/edit'),
        ];
    }
}
