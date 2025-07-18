<?php

namespace App\Filament\Resources\ServerMovies\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ServerMovieForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('movie_name')
                    ->required(),
                TextInput::make('tmdb_id'),
                Textarea::make('movie_link')
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->columnSpanFull(),
                Textarea::make('screenshots')
                    ->columnSpanFull(),
                Select::make('host_server_id')
                    ->label('Servidor Host')
                    ->relationship('hostServer', 'name')
                    ->searchable()
                    ->preload(),
            ]);
    }
}
