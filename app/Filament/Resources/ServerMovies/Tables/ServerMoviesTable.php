<?php

namespace App\Filament\Resources\ServerMovies\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use App\Services\WordPressImdbService;
use Illuminate\Database\Eloquent\Collection;

class ServerMoviesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc') # Ordenar por fecha de creación
            ->defaultPaginationPageOption(25) # Por defecto mostrar 25 registros
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
                    ->color(fn($state): ?string => $state['color'] ?? 'gray')
                    ->getStateUsing(function ($record) {
                        return $record->movieLinks
                            ->map(fn($link) => [
                                'label' => $link->hostServer->name ?? 'Desconocido',
                                'color' => $link->hostServer->badge_color ?? 'gray',
                            ])
                            ->unique('label')
                            ->values()
                            ->toArray();
                    })
                    ->formatStateUsing(fn($state) => $state['label'] ?? '-'),
                TextColumn::make('associatedWebs.get_domain')
                    ->label('Dominios')
                    ->badge()
                    ->limitList(3) // Opcional: limita la cantidad visible
                    ->listWithLineBreaks(false) // true para hacerlos verticales
                    ->color(fn($state): ?string => $state['color'] ?? 'gray')
                    ->getStateUsing(function ($record) {
                        return $record->associatedWebs
                            ->flatMap(fn($web) => $web->movieLinks->map(fn($link) => [
                                'label' => $web->get_domain,
                                'color' => $web->badge_color ?? 'gray',
                            ]))
                            ->unique('label') // evita repetir el mismo dominio
                            ->values()
                            ->toArray();
                    })
                    ->formatStateUsing(fn($state) => $state['label'] ?? '-'),
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
                    BulkAction::make('syncWithWordPress')
                        ->label('Sincronizar con WordPress')
                        ->icon('heroicon-o-cloud-arrow-up')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Sincronizar películas seleccionadas')
                        ->modalDescription('Se buscarán automáticamente en ClubPeli y OnliPeli los posts con los TMDB IDs de las películas seleccionadas.')
                        ->action(function (Collection $records) {
                            $service = app(WordPressImdbService::class);

                            $totalMovies = $records->count();
                            $totalCreated = 0;
                            $totalUpdated = 0;
                            $skipped = 0;

                            foreach ($records as $serverMovie) {
                                // Validar que tenga tmdb_id
                                if (!$serverMovie->tmdb_id) {
                                    $skipped++;
                                    continue;
                                }

                                // Validar que tenga MovieLinks
                                $movieLinks = $serverMovie->movieLinks;
                                if ($movieLinks->isEmpty()) {
                                    $skipped++;
                                    continue;
                                }

                                // Obtener el primer MovieLink
                                $firstMovieLink = $movieLinks->first();
                                $urlToSend = $firstMovieLink->movie_link;

                                if (!$urlToSend) {
                                    $skipped++;
                                    continue;
                                }

                                // Buscar en ambos sitios
                                $sites = [
                                    'clubpeli.com' => ['color' => 'success'],
                                    'onlipeli.net' => ['color' => 'info'],
                                ];

                                foreach ($sites as $domain => $config) {
                                    $posts = $service->findPostsByTmdbId($serverMovie->tmdb_id, $domain);

                                    if ($posts->isEmpty()) {
                                        continue;
                                    }

                                    foreach ($posts as $post) {
                                        // Verificar si ya existe
                                        $existingWeb = $serverMovie->associatedWebs()
                                            ->where('link', $post->getFullUrl($domain))
                                            ->first();

                                        if ($existingWeb) {
                                            continue;
                                        }

                                        // Crear AssociatedWeb
                                        $associatedWeb = $serverMovie->associatedWebs()->create([
                                            'link' => $post->getFullUrl($domain),
                                            'get_domain' => $domain,
                                            'badge_color' => $config['color'],
                                        ]);

                                        // Asociar todos los MovieLinks
                                        foreach ($movieLinks as $movieLink) {
                                            $associatedWeb->movieLinkDetails()->create([
                                                'movie_link_id' => $movieLink->id,
                                                'was_updated' => true,
                                            ]);
                                        }

                                        // Enviar a WordPress
                                        if ($service->updateUrlFilm($post, $urlToSend, $domain)) {
                                            $totalUpdated++;
                                        }

                                        $totalCreated++;
                                    }
                                }
                            }

                            $message = "Procesadas {$totalMovies} películas: {$totalCreated} registros creados, {$totalUpdated} actualizados en WordPress";
                            if ($skipped > 0) {
                                $message .= " ({$skipped} omitidas sin tmdb_id o MovieLinks)";
                            }

                            Notification::make()
                                ->success()
                                ->title('Sincronización masiva completada')
                                ->body($message)
                                ->send();
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
