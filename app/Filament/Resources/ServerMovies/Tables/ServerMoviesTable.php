<?php

namespace App\Filament\Resources\ServerMovies\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ServerMoviesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('movie_name')
                    ->searchable(),
                TextColumn::make('tmdb_id')
                    ->searchable(),
                TextColumn::make('movieLinks.hostServer.name')
                    ->label('Servidores')
                    ->badge()
                    ->sortable()
                    ->limitList(3)
                    ->listWithLineBreaks(false)
                    ->color(fn ($state): ?string => $state['color'] ?? 'gray')
                    ->getStateUsing(function ($record) {
                        return $record->movieLinks
                            ->map(fn ($link) => [
                                'label' => $link->hostServer->name ?? 'Desconocido',
                                'color' => $link->hostServer->badge_color ?? 'gray',
                            ])
                            ->unique('label')
                            ->values()
                            ->toArray();
                    })
                    ->formatStateUsing(fn ($state) => $state['label'] ?? '-'),
                TextColumn::make('associatedWebs.get_domain')
                    ->label('Dominios')
                    ->badge()
                    ->limitList(3) // Opcional: limita la cantidad visible
                    ->listWithLineBreaks(false) // true para hacerlos verticales
                    ->color(fn ($state): ?string => $state['color'] ?? 'gray')
                    ->getStateUsing(function ($record) {
                        return $record->associatedWebs
                            ->flatMap(fn ($web) => $web->movieLinks->map(fn ($link) => [
                                'label' => $web->get_domain,
                                'color' => $web->badge_color ?? 'gray',
                            ]))
                            ->unique('label') // evita repetir el mismo dominio
                            ->values()
                            ->toArray();
                    })
                    ->formatStateUsing(fn ($state) => $state['label'] ?? '-'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
}
