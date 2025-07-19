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
                TextColumn::make('hostServer.name')
                    ->badge()
                    ->sortable(),
                TextColumn::make('associatedWeb.get_domain')
                    ->label('Dominios')
                    ->badge()
                    ->limitList(3) // Opcional: limita la cantidad visible
                    ->listWithLineBreaks(false) // true para hacerlos verticales
                    ->color('gray') // color base
                    ->getStateUsing(function ($record) {
                        return $record->associatedWeb
                            ->pluck('get_domain')
                            ->unique()
                            ->filter()
                            ->values()
                            ->toArray();
                    }),
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
