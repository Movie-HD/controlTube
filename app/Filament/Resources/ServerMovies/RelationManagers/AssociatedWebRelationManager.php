<?php

namespace App\Filament\Resources\ServerMovies\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Forms\Components\Hidden;
use Filament\Tables\Grouping\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use App\Services\WordPressImdbService;
use App\Models\AssociatedWeb;

class AssociatedWebRelationManager extends RelationManager
{
    protected static string $relationship = 'associatedWebs';

    /**
     * Extrae el dominio de un link de AssociatedWeb
     */
    protected function getDomainFromLink(string $link): string
    {
        $parsed = parse_url($link);
        return $parsed['host'] ?? 'clubpeli.com';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('link')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, ?string $state) {
                        $domain = null;

                        if (filter_var($state, FILTER_VALIDATE_URL)) {
                            $parsedUrl = parse_url($state);
                            $domain = $parsedUrl['host'] ?? null;
                        }

                        $set('get_domain', $domain);
                    }),
                Hidden::make('get_domain')
                    ->dehydrated(true),
                Select::make('badge_color')
                    ->label('Color del dominio')
                    ->options([
                        'success' => 'Verde',
                        'danger' => 'Rojo',
                        'gray' => 'Gris',
                        'info' => 'Azul',
                        'warning' => 'Amarillo',
                    ])
                    ->default('gray'),

                // Repeater de relación con movie_links
                Repeater::make('movieLinkDetails')
                    ->hiddenLabel()
                    ->relationship() // <- ahora sí es un hasMany real
                    ->table([
                        TableColumn::make('Host'),
                        TableColumn::make('¿Fue actualizado?'),
                    ])
                    ->extraAttributes(['class' => 'mi-clase-td'])
                    ->schema([
                        Select::make('movie_link_id')
                            ->label('Enlace de película')
                            ->relationship(
                                name: 'movieLink',
                                titleAttribute: 'movie_link',
                                # 3. Filtramos los MovieLink para que su server_movie_id coincida con el ID del ServerMovie padre
                                modifyQueryUsing: fn(Builder $query) => $query->where('server_movie_id', $this->getOwnerRecord()->id)
                            )
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->native(false)
                            ->required(),
                        Toggle::make('was_updated')
                            ->label('¿Fue actualizado?')
                            ->default(true),
                    ])
                    ->addActionLabel('Nuevo enlace')
                    ->reorderable(false)
                    ->columnSpanFull()
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('link'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('link')
            ->columns([
                TextColumn::make('link')
                    ->searchable(),
            ])
            ->groups([
                Group::make('get_domain')
                    ->label('Dominio')
                    ->collapsible()
                    ->orderQueryUsing(
                        fn($query, $direction) =>
                        $query
                            ->orderBy('get_domain')
                            ->orderBy('created_at', 'desc')
                    ),
            ])
            ->defaultGroup('get_domain') # Aplica la agrupación por defecto
            ->groupingSettingsHidden()  # Oculta opciones de configuración de agrupación
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),

                // Botón para crear AssociatedWebs desde WordPress y enviar movie_link
                Action::make('createFromWordPress')
                    ->label('Crear y Enviar a WordPress')
                    ->icon('heroicon-o-cloud-arrow-up')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Crear AssociatedWebs y Enviar a WordPress')
                    ->modalDescription('Se buscarán automáticamente en ClubPeli y OnliPeli los posts con el mismo TMDB ID.')
                    ->action(function () {
                        $serverMovie = $this->getOwnerRecord();

                        if (!$serverMovie->tmdb_id) {
                            Notification::make()
                                ->warning()
                                ->title('No se puede sincronizar')
                                ->body('Esta película no tiene tmdb_id configurado')
                                ->send();
                            return;
                        }

                        $movieLinks = $serverMovie->movieLinks;

                        if ($movieLinks->isEmpty()) {
                            Notification::make()
                                ->warning()
                                ->title('Sin MovieLinks')
                                ->body('Esta película no tiene MovieLinks asociados. Crea al menos uno primero.')
                                ->send();
                            return;
                        }

                        // Obtener el primer MovieLink (automático)
                        $firstMovieLink = $movieLinks->first();
                        $urlToSend = $firstMovieLink->movie_link;

                        if (!$urlToSend) {
                            Notification::make()
                                ->warning()
                                ->title('MovieLink sin URL')
                                ->body('El primer MovieLink no tiene una URL configurada.')
                                ->send();
                            return;
                        }

                        $service = app(WordPressImdbService::class);
                        $totalCreated = 0;
                        $totalUpdated = 0;
                        $sitesSearched = [];

                        // Buscar en ambos sitios automáticamente
                        $sites = [
                            'clubpeli.com' => ['color' => 'success', 'name' => 'ClubPeli'],
                            'onlipeli.net' => ['color' => 'info', 'name' => 'OnliPeli'],
                        ];

                        foreach ($sites as $domain => $config) {
                            $posts = $service->findPostsByTmdbId($serverMovie->tmdb_id, $domain);

                            if ($posts->isEmpty()) {
                                $sitesSearched[] = "{$config['name']}: No encontrado";
                                continue;
                            }

                            $created = 0;
                            $updated = 0;

                            foreach ($posts as $post) {
                                // Verificar si ya existe un AssociatedWeb con este link
                                $existingWeb = $serverMovie->associatedWebs()
                                    ->where('link', $post->getFullUrl($domain))
                                    ->first();

                                if ($existingWeb) {
                                    continue; // Ya existe, saltar
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

                                // Enviar el primer MovieLink a WordPress
                                if ($service->updateUrlFilm($post, $urlToSend, $domain)) {
                                    $updated++;
                                }

                                $created++;
                            }

                            if ($created > 0) {
                                $sitesSearched[] = "{$config['name']}: {$created} creado(s), {$updated} actualizado(s)";
                                $totalCreated += $created;
                                $totalUpdated += $updated;
                            }
                        }

                        if ($totalCreated === 0) {
                            Notification::make()
                                ->warning()
                                ->title('No se encontraron películas')
                                ->body("No se encontraron posts en ningún sitio con tmdb_id: {$serverMovie->tmdb_id}")
                                ->send();
                            return;
                        }

                        Notification::make()
                            ->success()
                            ->title('Sincronización completada')
                            ->body(implode(' | ', $sitesSearched))
                            ->send();
                    }),

                // Botón para enviar todos los MovieLinks a WordPress
                Action::make('updateAllToWordPress')
                    ->label('Enviar Lote a WordPress')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Enviar todos a WordPress')
                    ->modalDescription('Se enviará el primer MovieLink de cada AssociatedWeb a WordPress.')
                    ->action(function () {
                        $serverMovie = $this->getOwnerRecord();
                        $service = app(WordPressImdbService::class);
                        $updated = 0;
                        $notFound = 0;

                        foreach ($serverMovie->associatedWebs as $associatedWeb) {
                            // Extraer dominio del link
                            $domain = $this->getDomainFromLink($associatedWeb->link);

                            // Extraer post_name del link
                            $postName = basename(parse_url($associatedWeb->link, PHP_URL_PATH));

                            $post = $service->findPostByPostName($postName, $domain);

                            if ($post) {
                                // Obtener el primer MovieLink asociado
                                $firstDetail = $associatedWeb->movieLinkDetails->first();

                                if ($firstDetail && $firstDetail->movieLink && $firstDetail->movieLink->movie_link) {
                                    $urlToSend = $firstDetail->movieLink->movie_link;

                                    // Enviar a WordPress
                                    if ($service->updateUrlFilm($post, $urlToSend, $domain)) {
                                        $updated++;
                                    }
                                } else {
                                    $notFound++;
                                }
                            } else {
                                $notFound++;
                            }
                        }

                        $message = "Se enviaron {$updated} enlaces a WordPress";
                        if ($notFound > 0) {
                            $message .= " ({$notFound} sin MovieLink o post no encontrado)";
                        }

                        Notification::make()
                            ->success()
                            ->title('Envío completado')
                            ->body($message)
                            ->send();
                    }),
            ])
            ->recordActions([
                // Botón para enviar un AssociatedWeb individual a WordPress
                Action::make('sendToWordPress')
                    ->label('Enviar a WordPress')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Enviar a WordPress')
                    ->modalDescription('Se enviará el primer MovieLink de este registro a WordPress.')
                    ->action(function (AssociatedWeb $record) {
                        $service = app(WordPressImdbService::class);

                        // Extraer dominio del link
                        $domain = $this->getDomainFromLink($record->link);

                        // Extraer post_name del link
                        $postName = basename(parse_url($record->link, PHP_URL_PATH));

                        $post = $service->findPostByPostName($postName, $domain);

                        if (!$post) {
                            Notification::make()
                                ->warning()
                                ->title('No encontrado')
                                ->body('No se encontró el post en WordPress')
                                ->send();
                            return;
                        }

                        // Obtener el primer MovieLink asociado
                        $firstDetail = $record->movieLinkDetails->first();

                        if (!$firstDetail || !$firstDetail->movieLink || !$firstDetail->movieLink->movie_link) {
                            Notification::make()
                                ->warning()
                                ->title('Sin MovieLink')
                                ->body('Este registro no tiene un MovieLink asociado con URL')
                                ->send();
                            return;
                        }

                        $urlToSend = $firstDetail->movieLink->movie_link;

                        // Enviar a WordPress
                        if ($service->updateUrlFilm($post, $urlToSend, $domain)) {
                            Notification::make()
                                ->success()
                                ->title('Envío exitoso')
                                ->body("Se envió el enlace a WordPress: {$urlToSend}")
                                ->send();
                        } else {
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('No se pudo actualizar WordPress')
                                ->send();
                        }
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
