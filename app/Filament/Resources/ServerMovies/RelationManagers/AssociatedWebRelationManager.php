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
use Filament\Schemas\Components\Flex;
use Filament\Forms\Components\Textarea;

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
                ToggleColumn::make('wp_edit_completed')
                    ->label('¿Fue editado en WordPress?')
                    ->alignCenter(),
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
                CreateAction::make()
                    ->label('Asociar Web Manual')
                    ->modalWidth('7xl')
                    ->color('gray'),

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

                            $foundPosts = $posts->count();

                            if ($foundPosts === 0) {
                                $sitesSearched[] = "{$config['name']}: No encontrado";
                                continue;
                            }

                            $created = 0;
                            $updated = 0;
                            $alreadyExists = 0;

                            foreach ($posts as $post) {
                                // Verificar si ya existe un AssociatedWeb con este link
                                $existingWeb = $serverMovie->associatedWebs()
                                    ->where('link', $post->getFullUrl($domain))
                                    ->first();

                                $wpEditUrl = "https://{$domain}/wp-admin/post.php?post={$post->ID}&action=edit";

                                if ($existingWeb) {
                                    // Si existe, asegurarnos que tenga el wp_edit_url
                                    if (empty($existingWeb->wp_edit_url)) {
                                        $existingWeb->update([
                                            'wp_edit_url' => $wpEditUrl,
                                            'wp_edit_completed' => false,
                                        ]);
                                    }

                                    // Intentar actualizar el link en WordPress de todas formas
                                    if ($service->updateUrlFilm($post, $urlToSend, $domain)) {
                                        $updated++;
                                    }

                                    $alreadyExists++;
                                    continue;
                                }

                                // Crear AssociatedWeb
                                $associatedWeb = $serverMovie->associatedWebs()->create([
                                    'link' => $post->getFullUrl($domain),
                                    'get_domain' => $domain,
                                    'badge_color' => $config['color'],
                                    'wp_edit_url' => $wpEditUrl,
                                    'wp_edit_completed' => false,
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

                            $sitesSearched[] = "{$config['name']}: {$foundPosts} encontrados ({$created} nuevos, {$updated} actualizados)";
                            $totalCreated += $created;
                            $totalUpdated += $updated;
                        }

                        if (empty($sitesSearched)) {
                            Notification::make()
                                ->warning()
                                ->title('No se encontraron películas')
                                ->body("No se encontraron posts en ningún sitio con tmdb_id: {$serverMovie->tmdb_id}")
                                ->send();
                            return;
                        }

                        Notification::make()
                            ->success()
                            ->title('Proceso completado')
                            ->body(implode("\n", $sitesSearched))
                            ->send();
                    }),


                // Botón para crear posts desde TMDB en ambos sitios
                Action::make('createPostFromTmdb')
                    ->label('Crear Posts desde TMDB')
                    ->icon('heroicon-o-sparkles')
                    ->color('info')
                    ->modalWidth('7xl')
                    ->form(function () {
                        $serverMovie = $this->getOwnerRecord();

                        if (!$serverMovie->tmdb_id) {
                            return [
                                \Filament\Forms\Components\Placeholder::make('error')
                                    ->content('Esta película no tiene tmdb_id configurado'),
                            ];
                        }

                        // Obtener datos de TMDB
                        $tmdbService = app(\App\Services\TmdbService::class);
                        $movieData = $tmdbService->getMovie($serverMovie->tmdb_id);

                        if (!$movieData) {
                            return [
                                \Filament\Forms\Components\Placeholder::make('error')
                                    ->content('No se pudieron obtener los datos de TMDB'),
                            ];
                        }

                        $formattedData = $tmdbService->formatForWordPress($movieData);

                        return [
                            Flex::make([
                                // Sección ClubPeli
                                \Filament\Schemas\Components\Section::make('ClubPeli.com')
                                    ->description('Configuración para ClubPeli')
                                    ->schema([
                                        Hidden::make('clubpeli_existing_id'),
                                        Hidden::make('clubpeli_exists')->default(false),

                                        \Filament\Forms\Components\Placeholder::make('clubpeli_status')
                                            ->content(function (callable $get) {
                                                if ($get('clubpeli_exists')) {
                                                    $wpAdminUrl = "https://clubpeli.com/wp-admin/post.php?post={$get('clubpeli_existing_id')}&action=edit";
                                                    return new \Illuminate\Support\HtmlString(
                                                        '⚠️ Ya existe. <a href="' . $wpAdminUrl . '" target="_blank" style="color: #3b82f6; text-decoration: underline;">Editar en WordPress →</a>'
                                                    );
                                                }
                                                return '✓ No existe. Se creará nuevo.';
                                            })
                                            ->visible(fn(callable $get) => $get('clubpeli_title') !== null)
                                            ->columnSpanFull(),

                                        TextInput::make('clubpeli_title')
                                            ->label('Título')
                                            ->default($formattedData['post_title'])
                                            ->reactive()
                                            ->afterStateUpdated(
                                                fn($state, callable $set) =>
                                                $set('clubpeli_slug', \Str::slug($state))
                                            ),

                                        TextInput::make('clubpeli_slug')
                                            ->label('Slug')
                                            ->default($formattedData['post_name'])
                                            ->reactive()
                                            ->afterStateHydrated(function ($state, callable $set) {
                                                // Verificar existencia al cargar el formulario
                                                if ($state) {
                                                    $wpService = app(WordPressImdbService::class);
                                                    $existing = $wpService->findPostByPostName($state, 'clubpeli.com');
                                                    $set('clubpeli_existing_id', $existing ? $existing->ID : null);
                                                    $set('clubpeli_exists', (bool) $existing);
                                                }
                                            })
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if ($state) {
                                                    $wpService = app(WordPressImdbService::class);
                                                    $existing = $wpService->findPostByPostName($state, 'clubpeli.com');
                                                    $set('clubpeli_existing_id', $existing ? $existing->ID : null);
                                                    $set('clubpeli_exists', (bool) $existing);
                                                }
                                            }),
                                    ])
                                    ->columns(2)
                                    ->collapsible(),

                                // Sección OnliPeli
                                \Filament\Schemas\Components\Section::make('OnliPeli.net')
                                    ->description('Configuración para OnliPeli')
                                    ->schema([
                                        Hidden::make('onlipeli_existing_id'),
                                        Hidden::make('onlipeli_exists')->default(false),

                                        \Filament\Forms\Components\Placeholder::make('onlipeli_status')
                                            ->content(function (callable $get) {
                                                if ($get('onlipeli_exists')) {
                                                    $wpAdminUrl = "https://onlipeli.net/wp-admin/post.php?post={$get('onlipeli_existing_id')}&action=edit";
                                                    return new \Illuminate\Support\HtmlString(
                                                        '⚠️ Ya existe. <a href="' . $wpAdminUrl . '" target="_blank" style="color: #3b82f6; text-decoration: underline;">Editar en WordPress →</a>'
                                                    );
                                                }
                                                return '✓ No existe. Se creará nuevo.';
                                            })
                                            ->visible(fn(callable $get) => $get('onlipeli_title') !== null)
                                            ->columnSpanFull(),

                                        TextInput::make('onlipeli_title')
                                            ->label('Título')
                                            ->default($formattedData['post_title'])
                                            ->reactive()
                                            ->afterStateUpdated(
                                                fn($state, callable $set) =>
                                                $set('onlipeli_slug', \Str::slug($state))
                                            ),

                                        TextInput::make('onlipeli_slug')
                                            ->label('Slug')
                                            ->default($formattedData['post_name'])
                                            ->reactive()
                                            ->afterStateHydrated(function ($state, callable $set) {
                                                // Verificar existencia al cargar el formulario
                                                if ($state) {
                                                    $wpService = app(WordPressImdbService::class);
                                                    $existing = $wpService->findPostByPostName($state, 'onlipeli.net');
                                                    $set('onlipeli_existing_id', $existing ? $existing->ID : null);
                                                    $set('onlipeli_exists', (bool) $existing);
                                                }
                                            })
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if ($state) {
                                                    $wpService = app(WordPressImdbService::class);
                                                    $existing = $wpService->findPostByPostName($state, 'onlipeli.net');
                                                    $set('onlipeli_existing_id', $existing ? $existing->ID : null);
                                                    $set('onlipeli_exists', (bool) $existing);
                                                }
                                            }),
                                    ])
                                    ->columns(2)
                                    ->collapsible(),
                            ])->from('md'),

                            // Campos comunes
                            \Filament\Schemas\Components\Section::make('Contenido Común')
                                ->description('Estos campos se aplicarán a ambos sitios')
                                ->schema([
                                    Textarea::make('post_content')
                                        ->label('Contenido')
                                        ->rows(5)
                                        ->default($formattedData['post_content'])
                                        ->columnSpanFull(),

                                    Textarea::make('post_excerpt')
                                        ->label('Extracto')
                                        ->rows(2)
                                        ->default($formattedData['post_excerpt'])
                                        ->columnSpanFull(),

                                    Select::make('post_status')
                                        ->label('Estado')
                                        ->options([
                                            'publish' => 'Publicado',
                                            'draft' => 'Borrador',
                                        ])
                                        ->default('draft'),

                                    TextInput::make('release_year')
                                        ->label('Año')
                                        ->default($formattedData['meta']['release_date']),

                                    TextInput::make('genres')
                                        ->label('Géneros')
                                        ->default($formattedData['meta']['genres'])
                                        ->columnSpanFull(),
                                ])
                                ->columns(2)
                                ->collapsible(),

                            // Campos IMDB solo para OnliPeli
                            \Filament\Schemas\Components\Section::make('Información IMDB (Solo OnliPeli)')
                                ->description('Estos campos se aplicarán solo a OnliPeli.net')
                                ->schema([
                                    TextInput::make('imdbRating')
                                        ->label('IMDB Rating')
                                        ->default($formattedData['meta']['imdbRating'])
                                        ->helperText('Calificación de IMDB'),

                                    TextInput::make('imdbVotes')
                                        ->label('IMDB Votes')
                                        ->default($formattedData['meta']['imdbVotes'])
                                        ->helperText('Número de votos'),

                                    TextInput::make('original_title')
                                        ->label('Original Title')
                                        ->default($formattedData['meta']['Title'])
                                        ->columnSpanFull(),

                                    TextInput::make('rated')
                                        ->label('Rated')
                                        ->default($formattedData['meta']['Rated'])
                                        ->helperText('Clasificación (ej: PG-13, R)'),

                                    TextInput::make('released')
                                        ->label('Release Date')
                                        ->default($formattedData['meta']['Released']),

                                    TextInput::make('runtime')
                                        ->label('Runtime')
                                        ->default($formattedData['meta']['Runtime'])
                                        ->helperText('Duración de la película'),

                                    TextInput::make('awards')
                                        ->label('Awards')
                                        ->default($formattedData['meta']['Awards'])
                                        ->helperText('Premios recibidos'),

                                    TextInput::make('country')
                                        ->label('Country')
                                        ->default($formattedData['meta']['Country'])
                                        ->columnSpanFull(),
                                ])
                                ->columns(2)
                                ->collapsible()
                                ->collapsed(),
                        ];
                    })
                    ->action(function (array $data) {
                        $serverMovie = $this->getOwnerRecord();
                        $created = 0;
                        $skipped = 0;
                        $results = [];

                        $sites = [
                            'clubpeli' => [
                                'domain' => 'clubpeli.com',
                                'connection' => 'wordpress',
                                'tmdb_field' => 'imdbLink',
                                'color' => 'success',
                            ],
                            'onlipeli' => [
                                'domain' => 'onlipeli.net',
                                'connection' => 'onlipeli',
                                'tmdb_field' => 'Checkbx2',
                                'color' => 'info',
                            ],
                        ];

                        foreach ($sites as $key => $config) {
                            // Skip si ya existe
                            if ($data["{$key}_exists"]) {
                                $results[] = "{$config['domain']}: Ya existe (omitido)";
                                $skipped++;
                                continue;
                            }

                            // Crear post
                            $postData = [
                                'post_title' => $data["{$key}_title"],
                                'post_name' => $data["{$key}_slug"],
                                'post_content' => $data['post_content'] ?? '',
                                'post_excerpt' => $data['post_excerpt'] ?? '',
                                'post_status' => $data['post_status'],
                                'post_type' => 'post',
                                'post_author' => 1,
                                'post_date' => now(),
                                'post_date_gmt' => now(),
                                'post_modified' => now(),
                                'post_modified_gmt' => now(),
                            ];

                            $postId = \DB::connection($config['connection'])->table('posts')->insertGetId($postData);

                            // Agregar metadata base
                            $metaInserts = [
                                ['post_id' => $postId, 'meta_key' => $config['tmdb_field'], 'meta_value' => $serverMovie->tmdb_id],
                                ['post_id' => $postId, 'meta_key' => 'release_date', 'meta_value' => $data['release_year'] ?? ''],
                                ['post_id' => $postId, 'meta_key' => 'genres', 'meta_value' => $data['genres'] ?? ''],
                            ];

                            // Agregar campos IMDB solo para OnliPeli
                            if ($key === 'onlipeli') {
                                $imdbFields = [
                                    ['post_id' => $postId, 'meta_key' => 'imdbRating', 'meta_value' => $data['imdbRating'] ?? ''],
                                    ['post_id' => $postId, 'meta_key' => 'imdbVotes', 'meta_value' => $data['imdbVotes'] ?? ''],
                                    ['post_id' => $postId, 'meta_key' => 'Title', 'meta_value' => $data['original_title'] ?? ''],
                                    ['post_id' => $postId, 'meta_key' => 'Rated', 'meta_value' => $data['rated'] ?? ''],
                                    ['post_id' => $postId, 'meta_key' => 'Released', 'meta_value' => $data['released'] ?? ''],
                                    ['post_id' => $postId, 'meta_key' => 'Runtime', 'meta_value' => $data['runtime'] ?? ''],
                                    ['post_id' => $postId, 'meta_key' => 'Awards', 'meta_value' => $data['awards'] ?? ''],
                                    ['post_id' => $postId, 'meta_key' => 'Country', 'meta_value' => $data['country'] ?? ''],
                                ];
                                $metaInserts = array_merge($metaInserts, $imdbFields);
                            }

                            \DB::connection($config['connection'])->table('postmeta')->insert($metaInserts);

                            // Crear AssociatedWeb
                            $fullUrl = "https://{$config['domain']}/{$data["{$key}_slug"]}";
                            $wpEditUrl = "https://{$config['domain']}/wp-admin/post.php?post={$postId}&action=edit";

                            $associatedWeb = $serverMovie->associatedWebs()->create([
                                'link' => $fullUrl,
                                'get_domain' => $config['domain'],
                                'badge_color' => $config['color'],
                                'wp_edit_completed' => false,
                                'wp_edit_url' => $wpEditUrl,
                            ]);

                            // Auto-asignar MovieLinks si existen
                            $movieLinks = $serverMovie->movieLinks;
                            if ($movieLinks->isNotEmpty()) {
                                foreach ($movieLinks as $movieLink) {
                                    $associatedWeb->movieLinkDetails()->create([
                                        'movie_link_id' => $movieLink->id,
                                        'was_updated' => false,
                                    ]);
                                }

                                // Enviar el primer MovieLink a WordPress
                                $firstMovieLink = $movieLinks->first();
                                if ($firstMovieLink && $firstMovieLink->movie_link) {
                                    try {
                                        $wpService = app(WordPressImdbService::class);
                                        $post = $wpService->findPostByPostName($data["{$key}_slug"], $config['domain']);

                                        if ($post) {
                                            $wpService->updateUrlFilm($post, $firstMovieLink->movie_link, $config['domain']);
                                        }
                                    } catch (\Exception $e) {
                                        \Log::error('Error sending MovieLink to WordPress after creation', [
                                            'domain' => $config['domain'],
                                            'error' => $e->getMessage()
                                        ]);
                                    }
                                }
                            }

                            $results[] = "{$config['domain']}: Creado";
                            $created++;
                        }

                        Notification::make()
                            ->success()
                            ->title('Posts procesados')
                            ->body(implode(' | ', $results))
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
                                // Actualizar wp_edit_url si no existe
                                if (empty($associatedWeb->wp_edit_url)) {
                                    $wpEditUrl = "https://{$domain}/wp-admin/post.php?post={$post->ID}&action=edit";
                                    $associatedWeb->update([
                                        'wp_edit_url' => $wpEditUrl,
                                        'wp_edit_completed' => false,
                                    ]);
                                }

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
                # Botón para editar en WordPress
                Action::make('editInWordPress')
                    ->label('Editar en WP')
                    ->icon('heroicon-o-pencil-square')
                    ->color('info')
                    ->url(fn(AssociatedWeb $record) => $record->wp_edit_url, shouldOpenInNewTab: true)
                    ->visible(fn(AssociatedWeb $record) => !empty($record->wp_edit_url)),

                // Botón para enviar un AssociatedWeb individual a WordPress
                Action::make('sendToWordPress')
                    ->label('Enviar a WordPress')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Enviar a WordPress')
                    ->modalDescription('Se enviará el primer MovieLink de este registro a WordPress.')
                    ->action(function (AssociatedWeb $record) {
                        try {
                            $service = app(WordPressImdbService::class);

                            // Extraer dominio del link
                            $domain = $this->getDomainFromLink($record->link);
                            \Log::info('Sending to WordPress', [
                                'domain' => $domain,
                                'link' => $record->link,
                            ]);

                            // Extraer post_name del link
                            $postName = basename(parse_url($record->link, PHP_URL_PATH));

                            $post = $service->findPostByPostName($postName, $domain);

                            if (!$post) {
                                \Log::warning('Post not found in WordPress', [
                                    'postName' => $postName,
                                    'domain' => $domain,
                                ]);
                                Notification::make()
                                    ->warning()
                                    ->title('No encontrado')
                                    ->body('No se encontró el post en WordPress')
                                    ->send();
                                return;
                            }

                            // Actualizar wp_edit_url si no existe
                            if (empty($record->wp_edit_url)) {
                                $wpEditUrl = "https://{$domain}/wp-admin/post.php?post={$post->ID}&action=edit";
                                $record->update([
                                    'wp_edit_url' => $wpEditUrl,
                                    'wp_edit_completed' => false,
                                ]);
                            }

                            // Obtener el primer MovieLink asociado
                            $firstDetail = $record->movieLinkDetails->first();

                            if (!$firstDetail || !$firstDetail->movieLink || !$firstDetail->movieLink->movie_link) {
                                \Log::warning('No MovieLink found', [
                                    'record_id' => $record->id,
                                ]);
                                Notification::make()
                                    ->warning()
                                    ->title('Sin MovieLink')
                                    ->body('Este registro no tiene MovieLinks asociados')
                                    ->send();
                                return;
                            }

                            $urlToSend = $firstDetail->movieLink->movie_link;
                            \Log::info('Attempting to send URL', [
                                'url' => $urlToSend,
                                'post_id' => $post->ID,
                                'domain' => $domain,
                            ]);

                            // Enviar a WordPress
                            $result = $service->updateUrlFilm($post, $urlToSend, $domain);

                            if ($result) {
                                \Log::info('Successfully sent to WordPress');
                                Notification::make()
                                    ->success()
                                    ->title('Enviado')
                                    ->body('MovieLink enviado exitosamente a WordPress')
                                    ->send();
                            } else {
                                \Log::error('updateUrlFilm returned false');
                                Notification::make()
                                    ->danger()
                                    ->title('Error')
                                    ->body('No se pudo enviar el MovieLink a WordPress')
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            \Log::error('Exception in sendToWordPress', [
                                'message' => $e->getMessage(),
                                'trace' => $e->getTraceAsString(),
                            ]);
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('Ocurrió un error: ' . $e->getMessage())
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
