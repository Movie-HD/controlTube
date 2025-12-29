<?php

namespace App\Filament\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Http;
use App\Services\TmdbService;

class TmdbTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->records(function (?string $search = null) {
                // Prioridad 1: Búsqueda interna de la tabla (si el usuario escribe en el buscador del modal)
                // Prioridad 2: Búsqueda inicial basada en el nombre de la película (sesión)
                $query = $search ?: session('tmdb_search_query');

                if (empty($query))
                    return [];

                $results = app(TmdbService::class)->searchMovie((string) $query);

                return collect($results)->keyBy('id')->toArray();
            })
            ->columns([
                \Filament\Tables\Columns\ImageColumn::make('poster_path')
                    ->label('Poster')
                    ->size(50)
                    ->circular()
                    ->getStateUsing(fn($record) => $record['poster_path'] ? "https://image.tmdb.org/t/p/w92{$record['poster_path']}" : null),
                TextColumn::make('title')
                    ->label('Título / Original')
                    ->description(fn($record) => $record['original_title'] ?? '')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('release_date')
                    ->label('Año')
                    ->alignCenter()
                    ->getStateUsing(fn($record) => $record['release_date'] ? substr($record['release_date'], 0, 4) : '-'),
                TextColumn::make('overview')
                    ->label('Sinopsis')
                    ->limit(50)
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('id')
                    ->label('TMDB ID')
                    ->fontFamily('mono')
                    ->copyable(),
            ]);
    }
}
