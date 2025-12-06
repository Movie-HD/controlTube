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
use App\Services\TmdbService;
use App\Models\AssociatedWeb;
use Filament\Schemas\Components\Flex;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\ViewField;

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
                TextColumn::make('movieLinkDetails.movieLink.movie_link')
                    ->badge()
                    ->color('gray')
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
                                    ->compact()
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
                                    ->compact()
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
                                ->compact()
                                ->schema([
                                    Textarea::make('synopsis')
                                        ->label('Sinopsis')
                                        ->rows(3)
                                        ->default($movieData['overview'] ?? $formattedData['post_content'])
                                        ->helperText('Se guardará en post_content y como meta "overview"')
                                        ->columnSpanFull(),

                                    TextInput::make('release_year')
                                        ->label('Año')
                                        ->default($formattedData['meta']['release_date'])
                                        ->helperText('Se guardará como "release_date" en ambos sitios y "Released" en OnliPeli'),

                                    TextInput::make('runtime')
                                        ->label('Duración (Runtime)')
                                        ->default($formattedData['meta']['Runtime'] ?? '')
                                        ->helperText('Duración de la película'),

                                    TextInput::make('vote_average')
                                        ->label('Calificación Promedio (IMDB Rating)')
                                        #->default($movieData['vote_average'] ?? '')
                                        ->default(fn() => number_format(mt_rand(79, 95) / 10, 1))
                                        ->helperText('Se guarda como "imdbRating" en OnliPeli y "vote_average" en ClubPeli'),

                                    TextInput::make('vote_count')
                                        ->label('Cantidad de Votos (IMDB Votes)')
                                        #->default($movieData['vote_count'] ?? '')
                                        ->default(fn() => number_format(mt_rand(45100, 79500), 0, '', ','))
                                        ->helperText('Se guarda como "imdbVotes" en OnliPeli y "vote_count" en ClubPeli'),

                                    TextInput::make('backdrops')
                                        ->label('Fondo Player (URL)')
                                        ->hidden()
                                        ->helperText('Una URL por línea. Se guardará como "fondo_player" en OnliPeli y "backdrop_film" en ClubPeli')
                                        ->columnSpanFull(),

                                    TextInput::make('trailer_youtube_id')
                                        ->label('Trailer YouTube ID')
                                        ->hidden()
                                        ->helperText('Solo el ID del video (ej: dQw4w9WgXcQ). Se guardará como "youtube_id" en OnliPeli y "trailers" en ClubPeli'),

                                    Select::make('font_typography')
                                        ->label('Tipografía/Font')
                                        ->options([
                                            'inherit' => 'DEFECT',
                                            'RINGM' => 'ACCION (RINGM)',
                                            'BlankaRegular' => 'TERROR (BlankaRegular)',
                                            'calendarnote' => 'ANIME (calendarnote)',
                                            'square77' => 'AMOR (square77)',
                                            'CaramelMocacino' => 'COMEDIA (CaramelMocacino)',
                                            'CARBON-DROID' => 'FAMILIA (CARBON-DROID)',
                                            'LOVEPbo' => 'AVENTURA (LOVEPbo)',
                                            'Lovelo2LineBold' => 'COMIC (Lovelo2LineBold)',
                                            'ValleniaLove' => 'ROMANCE (ValleniaLove)',
                                            'Vallenia' => 'FICCION (Vallenia)',
                                            'Fontrust' => 'ELEGANT (Fontrust)',
                                            'AwakeTheBeauty' => 'ESTILO (AwakeTheBeauty)',
                                        ])
                                        ->default('RINGM')
                                        ->hidden()
                                        ->helperText('Se guardará como "mainmovie" en OnliPeli y "font" en ClubPeli'),
                                ])
                                ->columns(2)
                                ->collapsible(),

                            // Campos IMDB solo para OnliPeli
                            \Filament\Schemas\Components\Section::make('Información IMDB (Solo OnliPeli)')
                                ->description('Estos campos se aplicarán solo a OnliPeli.net')
                                ->compact()
                                ->secondary()
                                ->schema([
                                    \Filament\Forms\Components\ToggleButtons::make('rated')
                                        ->label('Rated')
                                        ->options([
                                            'Todos' => 'Todos',
                                            '+ 12' => '+ 12',
                                            '+ 15' => '+ 15',
                                            '+ 18' => '+ 18',
                                        ])
                                        ->colors([
                                            'Todos' => 'success',
                                            '+ 12' => 'info',
                                            '+ 15' => 'warning',
                                            '+ 18' => 'danger',
                                        ])
                                        ->inline()
                                        ->grouped()
                                        #->default($formattedData['meta']['Rated'])
                                        ->helperText('Clasificación (ej: PG-13, R)'),

                                    TextInput::make('awards')
                                        ->label('Awards')
                                        ->default($formattedData['meta']['Awards'])
                                        ->autocomplete(false)
                                        ->datalist(['4 Wins & 2 nominations', '2 Wins & 2 nominations', '2 Wins & 1 nomination', '1 Win & 1 nomination'])
                                        ->helperText('Premios recibidos'),

                                    TextInput::make('original_title')
                                        ->label('Original Title')
                                        ->default($formattedData['meta']['Title']),

                                    TextInput::make('country')
                                        ->label('Country')
                                        ->default($formattedData['meta']['Country']),
                                ])
                                ->columns(2)
                                ->collapsible(),
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
                                'post_content' => $data['synopsis'] ?? '',
                                'post_excerpt' => '',
                                'post_status' => 'draft',
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
                            ];

                            // Campos comunes con nombres específicos por sitio
                            $commonFields = [];

                            // Overview/Sinopsis (también se guarda como meta)
                            if (!empty($data['synopsis'])) {
                                $commonFields[] = ['post_id' => $postId, 'meta_key' => 'overview', 'meta_value' => $data['synopsis']];
                            }

                            // Release Year/Date - nombres diferentes por sitio
                            if (!empty($data['release_year'])) {
                                $commonFields[] = ['post_id' => $postId, 'meta_key' => 'release_date', 'meta_value' => $data['release_year']];
                                // OnliPeli también usa "Released"
                                if ($key === 'onlipeli') {
                                    $commonFields[] = ['post_id' => $postId, 'meta_key' => 'Released', 'meta_value' => $data['release_year']];
                                }
                            }

                            // Runtime
                            if (!empty($data['runtime'])) {
                                $commonFields[] = ['post_id' => $postId, 'meta_key' => 'runtime', 'meta_value' => $data['runtime']];
                            }

                            // Vote Average y Vote Count - nombres diferentes por sitio
                            if (!empty($data['vote_average'])) {
                                $voteAvgKey = $key === 'onlipeli' ? 'imdbRating' : 'vote_average';
                                $commonFields[] = ['post_id' => $postId, 'meta_key' => $voteAvgKey, 'meta_value' => $data['vote_average']];
                            }

                            if (!empty($data['vote_count'])) {
                                $voteCountKey = $key === 'onlipeli' ? 'imdbVotes' : 'vote_count';
                                $commonFields[] = ['post_id' => $postId, 'meta_key' => $voteCountKey, 'meta_value' => $data['vote_count']];
                            }

                            // Backdrops - nombres diferentes por sitio
                            if (!empty($data['backdrops'])) {
                                $backdropKey = $key === 'onlipeli' ? 'fondo_player' : 'backdrop_film';
                                $commonFields[] = ['post_id' => $postId, 'meta_key' => $backdropKey, 'meta_value' => $data['backdrops']];
                            }

                            // Trailer YouTube ID - nombres diferentes por sitio
                            if (!empty($data['trailer_youtube_id'])) {
                                $trailerKey = $key === 'onlipeli' ? 'youtube_id' : 'trailers';
                                $commonFields[] = ['post_id' => $postId, 'meta_key' => $trailerKey, 'meta_value' => $data['trailer_youtube_id']];
                            }

                            // Font/Typography - nombres diferentes por sitio
                            if (!empty($data['font_typography'])) {
                                $fontKey = $key === 'onlipeli' ? 'mainmovie' : 'font';
                                $commonFields[] = ['post_id' => $postId, 'meta_key' => $fontKey, 'meta_value' => $data['font_typography']];
                            }

                            $metaInserts = array_merge($metaInserts, $commonFields);

                            // Agregar campos IMDB adicionales solo para OnliPeli (sin imdbRating/imdbVotes que ya están arriba)
                            if ($key === 'onlipeli') {
                                $imdbFields = [
                                    ['post_id' => $postId, 'meta_key' => 'Title', 'meta_value' => $data['original_title'] ?? ''],
                                    ['post_id' => $postId, 'meta_key' => 'Rated', 'meta_value' => $data['rated'] ?? ''],

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
                    ->label('Enviar MovieLink en Lote')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Enviar MovieLink en Lote a WordPress')
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

                // Nueva acción para editar campos básicos con preview
                Action::make('editBasicFields')
                    ->label('Editar Posts WP')
                    ->icon('heroicon-o-pencil-square')
                    ->color('info')
                    ->visible(fn() => $this->getOwnerRecord()->associatedWebs->isNotEmpty())
                    ->modalWidth('8xl')
                    ->fillForm(function () {
                        $serverMovie = $this->getOwnerRecord();
                        $tmdbService = app(TmdbService::class);

                        // Obtener datos de TMDB
                        $movieData = $tmdbService->getMovie($serverMovie->tmdb_id);
                        $formData = [];

                        // Para cada AssociatedWeb, cargar sus datos actuales
                        foreach ($serverMovie->associatedWebs as $associatedWeb) {
                            $domain = $this->getDomainFromLink($associatedWeb->link);
                            // Usar ID del AssociatedWeb para clave única (permite múltiples posts del mismo dominio)
                            $key = "post_{$associatedWeb->id}";
                            $siteType = $domain === 'clubpeli.com' ? 'clubpeli' : 'onlipeli';

                            \Log::info("Loading data for domain: {$domain}, key: {$key}, siteType: {$siteType}");

                            // Obtener post de WordPress
                            $wpService = app(WordPressImdbService::class);
                            $slug = str_replace(['https://clubpeli.com/', 'https://onlipeli.net/', 'https://www.onlipeli.net/', 'https://www.clubpeli.com/'], '', $associatedWeb->link);
                            $slug = trim($slug, '/');
                            $post = $wpService->findPostByPostName($slug, $domain);

                            if ($post) {
                                \Log::info("Post found for {$key}: {$post->post_title} (ID: {$post->ID})");

                                // Log all meta keys for debugging
                                $allMeta = $post->meta->pluck('meta_value', 'meta_key')->toArray();
                                \Log::info("All meta keys for {$key}", ['meta_keys' => array_keys($allMeta)]);

                                // Define meta keys for each site (usar siteType, no key)
                                $metaKeys = [
                                    'release_date' => 'release_date',
                                    'runtime' => $siteType === 'onlipeli' ? 'Runtime' : 'runtime',
                                    'vote_average' => $siteType === 'onlipeli' ? 'imdbRating' : 'vote_average',
                                    'vote_count' => $siteType === 'onlipeli' ? 'imdbVotes' : 'vote_count',
                                    'backdrop' => $siteType === 'onlipeli' ? 'fondo_player' : 'backdrop_film',
                                    'trailer' => $siteType === 'onlipeli' ? 'youtube_id' : 'trailers',
                                    'font' => $siteType === 'onlipeli' ? 'mainmovie' : 'font',
                                ];

                                // Log WordPress values for each field
                                $wpValues = [];
                                foreach ($metaKeys as $fieldName => $metaKey) {
                                    $wpValues[$fieldName] = [
                                        'meta_key' => $metaKey,
                                        'value' => $post->meta->where('meta_key', $metaKey)->first()?->meta_value ?? 'NOT FOUND'
                                    ];
                                }
                                \Log::info("WordPress values for {$key}", $wpValues);

                                // Cargar meta fields actuales, con fallback a TMDB si están vacíos
                                $formData["{$key}_synopsis"] = $post->post_content ?: ($movieData['overview'] ?? '');

                                // Release year - usar TMDB si está vacío
                                $wpReleaseYear = $post->meta->where('meta_key', 'release_date')->first()?->meta_value ?? '';
                                $formData["{$key}_release_year"] = $wpReleaseYear ?: (isset($movieData['release_date']) ? substr($movieData['release_date'], 0, 4) : '');

                                // Runtime - detectar formato y transformar si es necesario
                                // OnliPeli usa "Runtime" (mayúscula), ClubPeli usa "runtime" (minúscula)
                                $runtimeKey = $siteType === 'onlipeli' ? 'Runtime' : 'runtime';
                                $wpRuntime = $post->meta->where('meta_key', $runtimeKey)->first()?->meta_value ?? '';
                                $originalRuntime = $wpRuntime;
                                $runtimeWasTransformed = false;

                                // Función para verificar si está en formato HH:MM:SS
                                $isValidTimeFormat = function ($value) {
                                    return preg_match('/^\d{2}:\d{2}:\d{2}$/', $value);
                                };

                                if ($wpRuntime) {
                                    if (!$isValidTimeFormat($wpRuntime)) {
                                        // Intentar transformar diferentes formatos
                                        $runtimeWasTransformed = true;

                                        // Extraer solo números (ej: "155 min" -> 155, "2h 35m" -> intenta parsear)
                                        if (preg_match('/^(\d+)\s*(min|minutes)?$/i', $wpRuntime, $matches)) {
                                            // Formato: "155" o "155 min"
                                            $totalMinutes = (int) $matches[1];
                                            $hours = floor($totalMinutes / 60);
                                            $minutes = $totalMinutes % 60;
                                            $wpRuntime = sprintf('%02d:%02d:00', $hours, $minutes);
                                        } elseif (preg_match('/^(\d+)h?\s*(\d+)?m?$/i', $wpRuntime, $matches)) {
                                            // Formato: "2h 35m" o "2h35m" o "2 35"
                                            $hours = (int) $matches[1];
                                            $minutes = isset($matches[2]) ? (int) $matches[2] : 0;
                                            $wpRuntime = sprintf('%02d:%02d:00', $hours, $minutes);
                                        } elseif (preg_match('/^(\d{1,2}):(\d{2})$/', $wpRuntime, $matches)) {
                                            // Formato: "2:35" o "02:35" (sin segundos)
                                            $wpRuntime = sprintf('%02d:%02d:00', (int) $matches[1], (int) $matches[2]);
                                        } else {
                                            // Si no podemos parsear, dejamos el original pero marcamos como sin transformar exitosa
                                            $wpRuntime = $originalRuntime;
                                            $runtimeWasTransformed = false;
                                        }
                                    }
                                } elseif (isset($movieData['runtime'])) {
                                    // No hay runtime en WP, usar TMDB
                                    $hours = floor($movieData['runtime'] / 60);
                                    $minutes = $movieData['runtime'] % 60;
                                    $wpRuntime = sprintf('%02d:%02d:00', $hours, $minutes);
                                    $originalRuntime = "TMDB: {$movieData['runtime']} min";
                                    $runtimeWasTransformed = true;
                                }

                                $formData["{$key}_runtime"] = $wpRuntime;
                                $formData["{$key}_runtime_original"] = $originalRuntime;
                                $formData["{$key}_runtime_transformed"] = $runtimeWasTransformed;

                                // Votes - usar TMDB si está vacío (usar siteType para determinar meta keys)
                                $voteAvgKey = $siteType === 'onlipeli' ? 'imdbRating' : 'vote_average';
                                $voteCountKey = $siteType === 'onlipeli' ? 'imdbVotes' : 'vote_count';
                                $wpVoteAvg = $post->meta->where('meta_key', $voteAvgKey)->first()?->meta_value ?? '';
                                $wpVoteCount = $post->meta->where('meta_key', $voteCountKey)->first()?->meta_value ?? '';
                                $formData["{$key}_vote_average"] = $wpVoteAvg ?: ($movieData['vote_average'] ?? '');
                                $formData["{$key}_vote_count"] = $wpVoteCount ?: ($movieData['vote_count'] ?? '');

                                // Backdrop - usar TMDB si está vacío
                                $backdropKey = $siteType === 'onlipeli' ? 'fondo_player' : 'backdrop_film';
                                $wpBackdrop = $post->meta->where('meta_key', $backdropKey)->first()?->meta_value ?? '';
                                if (!$wpBackdrop && isset($movieData['backdrop_path'])) {
                                    $wpBackdrop = "https://image.tmdb.org/t/p/original{$movieData['backdrop_path']}";
                                }
                                $formData["{$key}_backdrops"] = $wpBackdrop;

                                // Trailer - mantener vacío si no existe en WP
                                $trailerKey = $siteType === 'onlipeli' ? 'youtube_id' : 'trailers';
                                $formData["{$key}_trailer_youtube_id"] = $post->meta->where('meta_key', $trailerKey)->first()?->meta_value ?? '';

                                // Font
                                $fontKey = $siteType === 'onlipeli' ? 'mainmovie' : 'font';
                                $formData["{$key}_font_typography"] = $post->meta->where('meta_key', $fontKey)->first()?->meta_value ?? 'inherit';

                                // Datos para preview
                                $formData["{$key}_poster"] = $movieData['poster_path'] ? "https://image.tmdb.org/t/p/w780{$movieData['poster_path']}" : '';
                                $formData["{$key}_title"] = $post->post_title;
                                $formData["{$key}_additional_image"] = $movieData['poster_path'] ? "https://image.tmdb.org/t/p/w300{$movieData['poster_path']}" : '';

                                // Guardar siteType para uso posterior
                                $formData["{$key}_site_type"] = $siteType;
                                $formData["{$key}_associated_web_id"] = $associatedWeb->id;

                                // Log final form data for this site
                                \Log::info("Final form data for {$key}", [
                                    'synopsis' => substr($formData["{$key}_synopsis"], 0, 50) . '...',
                                    'release_year' => $formData["{$key}_release_year"],
                                    'runtime' => $formData["{$key}_runtime"],
                                    'vote_average' => $formData["{$key}_vote_average"],
                                    'vote_count' => $formData["{$key}_vote_count"],
                                    'backdrops' => $formData["{$key}_backdrops"],
                                    'trailer' => $formData["{$key}_trailer_youtube_id"],
                                    'font' => $formData["{$key}_font_typography"],
                                ]);
                            } else {
                                \Log::warning("Post not found for {$key}, slug: {$slug}");
                            }
                        }

                        return $formData;
                    })
                    ->form(function () {
                        $serverMovie = $this->getOwnerRecord();
                        $tmdbService = app(TmdbService::class);
                        $movieData = $tmdbService->getMovie($serverMovie->tmdb_id);



                        // Obtener backdrops de TMDB
                        $tmdbBackdrops = [];

                        \Log::info("Movie data keys", ['keys' => array_keys($movieData ?? [])]);
                        \Log::info("Images data", ['images' => $movieData['images'] ?? 'not set']);

                        if (isset($movieData['images']['backdrops'])) {
                            foreach ($movieData['images']['backdrops'] as $backdrop) {
                                $tmdbBackdrops[] = "https://image.tmdb.org/t/p/w1280{$backdrop['file_path']}";
                            }
                            \Log::info("TMDB Backdrops loaded", ['count' => count($tmdbBackdrops), 'first' => $tmdbBackdrops[0] ?? 'none']);
                        } else {
                            \Log::warning("No TMDB backdrops found in movie data", ['has_images' => isset($movieData['images']), 'has_backdrops' => isset($movieData['images']['backdrops'])]);
                        }

                        $formSections = [];

                        // Crear una sección por cada AssociatedWeb
                        foreach ($serverMovie->associatedWebs as $associatedWeb) {
                            $domain = $this->getDomainFromLink($associatedWeb->link);
                            // Usar ID del AssociatedWeb para clave única
                            $key = "post_{$associatedWeb->id}";
                            $siteType = $domain === 'clubpeli.com' ? 'clubpeli' : 'onlipeli';
                            $siteLabel = $siteType === 'clubpeli' ? 'ClubPeli' : 'OnliPeli';

                            // Obtener título del post para mostrar en la sección
                            $wpService = app(WordPressImdbService::class);
                            $slug = str_replace(['https://clubpeli.com/', 'https://onlipeli.net/', 'https://www.onlipeli.net/', 'https://www.clubpeli.com/'], '', $associatedWeb->link);
                            $slug = trim($slug, '/');
                            $post = $wpService->findPostByPostName($slug, $domain);
                            $postTitle = $post ? $post->post_title : $slug;

                            $formSections[] = \Filament\Schemas\Components\Section::make("{$siteLabel}: {$postTitle}")
                                ->description("ID: {$associatedWeb->id} | {$associatedWeb->link}")
                                ->compact()
                                ->schema([
                                    // Hidden fields para preview y metadata
                                    \Filament\Forms\Components\Hidden::make("{$key}_poster"),
                                    \Filament\Forms\Components\Hidden::make("{$key}_title"),
                                    \Filament\Forms\Components\Hidden::make("{$key}_additional_image"),
                                    \Filament\Forms\Components\Hidden::make("{$key}_site_type"),
                                    \Filament\Forms\Components\Hidden::make("{$key}_associated_web_id"),

                                    // Campos editables
                                    \Filament\Forms\Components\Textarea::make("{$key}_synopsis")
                                        ->label('Sinopsis')
                                        ->rows(3)
                                        ->reactive()
                                        ->columnSpanFull(),

                                    \Filament\Forms\Components\TextInput::make("{$key}_release_year")
                                        ->label('Año')
                                        ->reactive(),

                                    // Hidden fields para tracking de runtime
                                    \Filament\Forms\Components\Hidden::make("{$key}_runtime_original"),
                                    \Filament\Forms\Components\Hidden::make("{$key}_runtime_transformed"),

                                    \Filament\Forms\Components\TextInput::make("{$key}_runtime")
                                        ->label('Duración')
                                        ->placeholder('HH:MM:SS')
                                        ->reactive()
                                        ->helperText(function ($get) use ($key) {
                                            $wasTransformed = $get("{$key}_runtime_transformed");
                                            $original = $get("{$key}_runtime_original");
                                            $current = $get("{$key}_runtime");

                                            if (!$current) {
                                                return '⚠️ Sin duración definida';
                                            }

                                            // Verificar si el formato actual es válido
                                            $isValid = preg_match('/^\d{2}:\d{2}:\d{2}$/', $current);

                                            if ($wasTransformed && $original) {
                                                return new \Illuminate\Support\HtmlString(
                                                    "✅ Transformado de <strong>{$original}</strong> → <strong>{$current}</strong>"
                                                );
                                            } elseif ($isValid) {
                                                return '✅ Formato correcto (HH:MM:SS)';
                                            } else {
                                                return new \Illuminate\Support\HtmlString(
                                                    '⚠️ Formato incorrecto. Debe ser <strong>HH:MM:SS</strong> (ej: 02:35:00)'
                                                );
                                            }
                                        }),

                                    \Filament\Forms\Components\TextInput::make("{$key}_vote_average")
                                        ->label('Calificación')
                                        ->reactive(),

                                    \Filament\Forms\Components\TextInput::make("{$key}_vote_count")
                                        ->label('Votos')
                                        ->reactive(),

                                    \Filament\Forms\Components\TextInput::make("{$key}_trailer_youtube_id")
                                        ->label('Trailer YouTube ID')
                                        ->reactive(),

                                    \Filament\Forms\Components\Select::make("{$key}_font_typography")
                                        ->label('Tipografía')
                                        ->options([
                                            'inherit' => 'DEFECT',
                                            'RINGM' => 'ACCION',
                                            'BlankaRegular' => 'TERROR',
                                            'calendarnote' => 'ANIME',
                                            'square77' => 'AMOR',
                                            'CaramelMocacino' => 'COMEDIA',
                                            'CARBON-DROID' => 'FAMILIA',
                                            'LOVEPbo' => 'AVENTURA',
                                            'Lovelo2LineBold' => 'COMIC',
                                            'ValleniaLove' => 'ROMANCE',
                                            'Vallenia' => 'FICCION',
                                            'Fontrust' => 'ELEGANT',
                                            'AwakeTheBeauty' => 'ESTILO',
                                        ])
                                        ->native(false)
                                        ->reactive(),

                                    \Filament\Forms\Components\TextInput::make("{$key}_backdrops")
                                        ->label('URL del Backdrop')
                                        ->placeholder('Selecciona una imagen de TMDB o ingresa una URL')
                                        ->live(debounce: 500)
                                        ->columnSpanFull(),

                                    // Preview iframe (después del selector)
                                    \Filament\Forms\Components\ViewField::make("{$key}_preview")
                                        ->label('Preview')
                                        ->view('filament.forms.components.live-preview-iframe')
                                        ->viewData(fn($get) => [
                                            'domain' => $domain,
                                            'baseUrl' => "https://clubpeli.com/wp-content/themes/diddli/masthemes/parts/iframe-preview.php",
                                            'poster' => $get("{$key}_poster"),
                                            'title' => $get("{$key}_title"),
                                            'year' => $get("{$key}_release_year"),
                                            'trailer' => $get("{$key}_trailer_youtube_id"),
                                            'synopsis' => $get("{$key}_synopsis"),
                                            'backdrop' => $get("{$key}_backdrops"),
                                            'font' => $get("{$key}_font_typography"),
                                            'additionalImage' => $get("{$key}_additional_image"),
                                            'previewId' => "{$key}_preview"
                                        ])
                                        ->columnSpanFull(),

                                    // Selector de backdrops TMDB
                                    \Filament\Forms\Components\ViewField::make("{$key}_backdrop_selector")
                                        ->label('Seleccionar de TMDB')
                                        ->view('filament.forms.components.tmdb-backdrop-selector')
                                        ->viewData([
                                            'backdrops' => $tmdbBackdrops,
                                            'targetField' => "data.{$key}_backdrops",
                                        ])
                                        ->columnSpanFull(),
                                ])
                                ->columns(2)
                                ->collapsible();
                        }

                        return [
                            Flex::make($formSections)
                                ->from('md'),
                        ];
                    })
                    ->action(function (array $data) {
                        $serverMovie = $this->getOwnerRecord();
                        $wpService = app(WordPressImdbService::class);
                        $updatedSites = [];
                        $skippedSites = [];

                        foreach ($serverMovie->associatedWebs as $associatedWeb) {
                            $domain = $this->getDomainFromLink($associatedWeb->link);
                            // Usar ID del AssociatedWeb para clave única (igual que en fillForm y form)
                            $key = "post_{$associatedWeb->id}";
                            $siteType = $domain === 'clubpeli.com' ? 'clubpeli' : 'onlipeli';
                            $connection = $domain === 'clubpeli.com' ? 'wordpress' : 'onlipeli';
                            $siteLabel = $siteType === 'clubpeli' ? 'ClubPeli' : 'OnliPeli';

                            // Obtener post de WordPress
                            $slug = str_replace(['https://clubpeli.com/', 'https://onlipeli.net/', 'https://www.clubpeli.com/', 'https://www.onlipeli.net/'], '', $associatedWeb->link);
                            $slug = trim($slug, '/');
                            $post = $wpService->findPostByPostName($slug, $domain);

                            if (!$post) {
                                \Log::warning("Post not found for save: {$domain}, slug: {$slug}");
                                continue;
                            }

                            // Obtener valores actuales de WordPress para comparar
                            $currentMeta = $post->meta->pluck('meta_value', 'meta_key')->toArray();
                            $currentSynopsis = $post->post_content;

                            // Definir mapeo de campos según el sitio (usar siteType)
                            $voteAvgKey = $siteType === 'onlipeli' ? 'imdbRating' : 'vote_average';
                            $voteCountKey = $siteType === 'onlipeli' ? 'imdbVotes' : 'vote_count';
                            $backdropKey = $siteType === 'onlipeli' ? 'fondo_player' : 'backdrop_film';
                            $trailerKey = $siteType === 'onlipeli' ? 'youtube_id' : 'trailers';
                            $fontKey = $siteType === 'onlipeli' ? 'mainmovie' : 'font';
                            $runTime = $siteType === 'onlipeli' ? 'Runtime' : 'runtime';

                            // Preparar nuevos valores del formulario (usar key único)
                            $newValues = [
                                'synopsis' => $data["{$key}_synopsis"] ?? '',
                                'overview' => $data["{$key}_synopsis"] ?? '',
                                'release_date' => $data["{$key}_release_year"] ?? '',
                                $runTime => $data["{$key}_runtime"] ?? '',
                                $voteAvgKey => $data["{$key}_vote_average"] ?? '',
                                $voteCountKey => $data["{$key}_vote_count"] ?? '',
                                $backdropKey => $data["{$key}_backdrops"] ?? '',
                                $trailerKey => $data["{$key}_trailer_youtube_id"] ?? '',
                                $fontKey => $data["{$key}_font_typography"] ?? 'inherit',
                            ];

                            if ($siteType === 'onlipeli') {
                                $newValues['Released'] = $data["{$key}_release_year"] ?? '';
                            }

                            // Detectar cambios
                            $hasChanges = false;
                            $changedFields = [];

                            // Comparar synopsis (post_content)
                            if (trim($currentSynopsis) !== trim($newValues['synopsis'])) {
                                $hasChanges = true;
                                $changedFields[] = 'Synopsis';
                            }

                            // Comparar meta fields
                            $metaFieldsToCheck = [
                                'overview' => 'Overview',
                                'release_date' => 'Año',
                                $runTime => 'Duración',
                                $voteAvgKey => 'Rating',
                                $voteCountKey => 'Votos',
                                $backdropKey => 'Backdrop',
                                $trailerKey => 'Trailer',
                                $fontKey => 'Font',
                            ];

                            foreach ($metaFieldsToCheck as $metaKey => $fieldLabel) {
                                $currentValue = trim($currentMeta[$metaKey] ?? '');
                                $newValue = trim($newValues[$metaKey] ?? '');

                                if ($currentValue !== $newValue) {
                                    $hasChanges = true;
                                    $changedFields[] = $fieldLabel;
                                }
                            }

                            // Solo actualizar si hay cambios
                            if (!$hasChanges) {
                                $skippedSites[] = "{$siteLabel}: {$post->post_title}";
                                \Log::info("No changes detected for {$siteLabel}: {$post->post_title}, skipping update");
                                continue;
                            }

                            \Log::info("Changes detected for {$siteLabel}: {$post->post_title}", ['fields' => $changedFields]);

                            // Actualizar post_content
                            \DB::connection($connection)->table('posts')
                                ->where('ID', $post->ID)
                                ->update(['post_content' => $newValues['synopsis']]);

                            // Preparar meta updates (sin synopsis que es post_content)
                            $metaUpdates = [
                                'overview' => $newValues['overview'],
                                'release_date' => $newValues['release_date'],
                                $runTime => $newValues[$runTime],
                                $voteAvgKey => $newValues[$voteAvgKey],
                                $voteCountKey => $newValues[$voteCountKey],
                                $backdropKey => $newValues[$backdropKey],
                                $trailerKey => $newValues[$trailerKey],
                                $fontKey => $newValues[$fontKey],
                            ];

                            if ($siteType === 'onlipeli') {
                                $metaUpdates['Released'] = $newValues['Released'];
                            }

                            // Actualizar cada meta field
                            foreach ($metaUpdates as $metaKey => $metaValue) {
                                \DB::connection($connection)->table('postmeta')
                                    ->updateOrInsert(
                                        ['post_id' => $post->ID, 'meta_key' => $metaKey],
                                        ['meta_value' => $metaValue]
                                    );
                            }

                            $updatedSites[] = "{$siteLabel}: {$post->post_title} (" . implode(', ', $changedFields) . ")";
                        }

                        // Mostrar notificación según los resultados
                        if (empty($updatedSites) && empty($skippedSites)) {
                            Notification::make()
                                ->warning()
                                ->title('Sin posts encontrados')
                                ->body('No se encontraron posts en WordPress para actualizar')
                                ->send();
                        } elseif (empty($updatedSites)) {
                            Notification::make()
                                ->info()
                                ->title('Sin cambios')
                                ->body('No se detectaron cambios en ningún sitio')
                                ->send();
                        } else {
                            $body = "Actualizados:\n" . implode("\n", $updatedSites);
                            if (!empty($skippedSites)) {
                                $body .= "\n\nSin cambios: " . implode(', ', $skippedSites);
                            }

                            Notification::make()
                                ->success()
                                ->title('Campos actualizados')
                                ->body($body)
                                ->send();
                        }
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
                    ->label('Enviar o Actualizar MovieLink a WP')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Enviar o Actualizar MovieLink a WP')
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

                \Filament\Actions\ActionGroup::make([
                    EditAction::make()
                        ->color('gray')
                        ->hiddenLabel(),
                    DeleteAction::make()
                        ->color('gray')
                        ->hiddenLabel(),
                ])->buttonGroup(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
