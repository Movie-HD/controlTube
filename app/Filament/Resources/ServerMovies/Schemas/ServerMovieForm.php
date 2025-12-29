<?php

namespace App\Filament\Resources\ServerMovies\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TableSelect;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Forms\Components\FileUpload;
use Filament\Actions\Action;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Schemas\Components\{Section, FusedGroup};
use Filament\Support\Icons\Heroicon;

class ServerMovieForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->extraAttributes(['class' => 'mi-clase-personalizada'])
            ->components([
                TextInput::make('movie_name')
                    ->required()
                    #->prefixIcon(Heroicon::MagnifyingGlassCircle)
                    ->suffixAction(
                        Action::make('search_tmdb')
                            ->label('Buscar en TMDB')
                            ->icon(Heroicon::MagnifyingGlassCircle)
                            ->schema(function (Get $get) {
                                $movieName = $get('movie_name');
                                session(['tmdb_search_query' => $movieName]);

                                return [
                                    TableSelect::make('tmdb_id')
                                        ->label('Resultados de TMDB para: ' . ($movieName ?: '...'))
                                        ->tableConfiguration(\App\Filament\Tables\TmdbTable::class)
                                        ->columnSpanFull(),
                                ];
                            })
                            ->action(function (array $data, Set $set) {
                                if (isset($data['tmdb_id'])) {
                                    $set('tmdb_id', $data['tmdb_id']);
                                }
                            })
                    ),
                TextInput::make('tmdb_id'),
                Section::make('Descripción y Archivos')
                    ->components([
                        Textarea::make('description')
                            ->columnSpanFull(),
                        FileUpload::make('screenshots')
                            ->label('Adjuntar Archivos')
                            ->preserveFilenames()
                            ->multiple()
                            ->reorderable()
                            ->appendFiles()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed(),
                Repeater::make('movieLinks')
                    ->relationship()
                    ->hiddenLabel()
                    ->table([
                        TableColumn::make('Link'),
                        TableColumn::make('Host'),
                        TableColumn::make('Calidad e Idioma'),
                        TableColumn::make('Referencia')
                            ->width('calc(150px + 5vw)'),
                    ])
                    ->schema([
                        TextInput::make('movie_link')
                            ->label('Enlace de la película')
                            ->required()
                            ->url()
                            ->suffixAction(
                                Action::make('open_link')
                                    ->icon(Heroicon::ArrowTopRightOnSquare)
                                    ->color('gray')
                                    ->url(fn(Get $get) => $get('movie_link'))
                                    ->openUrlInNewTab()
                                    ->visible(fn(Get $get) => filled($get('movie_link')))
                            ),

                        Select::make('host_server_id')
                            ->label('Servidor Host')
                            ->relationship('hostServer', 'name')
                            // ------------------------------------------------------------------
                            // PASO 1: INTERCEPTAR LA BÚSQUEDA
                            // ------------------------------------------------------------------
                            // Este método se ejecuta cada vez que el usuario escribe en el campo.
                            ->getSearchResultsUsing(function (string $search): array {
                                // GUARDAMOS en la sesión: Lo que escribió el usuario Y la hora actual.
                                // La hora es clave para saber si el dato sigue siendo válido o es "viejo".
                                session([
                                    'host_server_search' => [
                                        'term' => $search,        // El texto: "Ejemplo"
                                        'timestamp' => now()->timestamp // La hora exacta en segundos
                                    ]
                                ]);

                                // Ejecutamos la consulta normal en la base de datos
                                return \App\Models\HostServer::query()
                                    ->where('name', 'like', "%{$search}%")
                                    ->limit(50)
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })

                            # ✅ Agregamos hintActions que nos permite crear acciones, en este caso para crear y eliminar.
                            ->hintActions([
                                # Creamos un nuevo host server
                                Action::make('createPhoneNumber')
                                    ->label('Crear')
                                    ->icon('heroicon-m-plus')
                                    ->modalHeading('Creando nuevo Servidor de peliculas')
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Nombre')
                                            ->required()
                                            // ------------------------------------------------------------------
                                            // PASO 2: LÓGICA DE RECUPERACIÓN (DEFAULT)
                                            // ------------------------------------------------------------------
                                            // Cuando se abre el modal, evaluamos qué poner en este campo por defecto.
                                            ->default(function () {
                                                // 1. Recuperamos lo guardado en la sesión
                                                $datosBusqueda = session('host_server_search');

                                                // Definimos cuánto tiempo es válido el dato (en segundos)
                                                // Si el usuario abre el modal 2 minutos después de buscar, no usamos el dato.
                                                $tiempoDeVida = 15;

                                                // 2. Verificamos si existen datos y si tienen la marca de tiempo
                                                if ($datosBusqueda && isset($datosBusqueda['timestamp'])) {

                                                    // 3. Calculamos si la búsqueda es reciente
                                                    // (Hora actual - Hora de búsqueda) < Tiempo de vida ?
                                                    if (now()->timestamp - $datosBusqueda['timestamp'] < $tiempoDeVida) {
                                                        # Borramos la sesión inmediatamente
                                                        session()->forget('host_server_search');
                                                        // Si es reciente (ej. hace 5 segundos), devolvemos el texto buscado                                                        
                                                        return $datosBusqueda['term'];
                                                    }
                                                }

                                                // Si no hay datos, o si pasaron más de 60 segundos, devolvemos vacío.
                                                // Esto limpia el dato "viejo" automáticamente.
                                                return '';
                                            }),
                                        Select::make('badge_color')
                                            ->label('Color del badge')
                                            ->options([
                                                'success' => 'Verde',
                                                'danger' => 'Rojo',
                                                'gray' => 'Gris',
                                                'info' => 'Azul',
                                                'warning' => 'Amarillo',
                                            ])
                                            ->default('gray')
                                            ->native(false),
                                        Section::make('Detalles del Servidor')
                                            ->components([
                                                TextInput::make('description')
                                                    ->label('Descripción'),
                                                TextInput::make('email')
                                                    ->label('Correo'),
                                                TextInput::make('password')
                                                    ->label('Contraseña'),
                                                FileUpload::make('screenshots')
                                                    ->label('Adjuntar Archivos')
                                                    ->preserveFilenames()
                                                    ->multiple()
                                                    ->reorderable()
                                                    ->appendFiles(),
                                            ])
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->collapsed(),
                                    ])
                                    # ✅ Actualizamos el select 'host_server_id' con la nueva opción
                                    ->action(function (array $data, Set $set) {
                                        $new = \App\Models\HostServer::create($data);
                                        $set('host_server_id', $new->id);
                                    }),

                                # Eliminamos el host server
                                Action::make('deleteHostServer')
                                    ->label('Eliminar')
                                    ->icon('heroicon-m-trash')
                                    ->requiresConfirmation()
                                    ->color('danger')
                                    ->action(function (Get $get, Set $set) {
                                        $id = $get('host_server_id');
                                        if ($id) {
                                            \App\Models\HostServer::find($id)?->delete();
                                            $set('host_server_id', null);
                                        }
                                    })
                                    ->visible(fn(Get $get) => !is_null($get('host_server_id'))),
                            ])

                            # ✅ Usamos editOptionForm para editar el host server seleccionado. [Este metodo es de Filament]
                            ->editOptionForm([
                                TextInput::make('name')
                                    ->label('Nombre')
                                    ->required(),
                                Select::make('badge_color')
                                    ->label('Color del badge')
                                    ->options([
                                        'success' => 'Verde',
                                        'danger' => 'Rojo',
                                        'gray' => 'Gris',
                                        'info' => 'Azul',
                                        'warning' => 'Amarillo',
                                    ])
                                    ->default('gray')
                                    ->native(false),
                                Section::make('Detalles del Servidor')
                                    ->components([
                                        TextInput::make('description')
                                            ->label('Descripción'),
                                        TextInput::make('email')
                                            ->label('Correo'),
                                        TextInput::make('password')
                                            ->label('Contraseña'),
                                        FileUpload::make('screenshots')
                                            ->label('Adjuntar Archivos')
                                            ->image()
                                            ->imageEditor()
                                            ->preserveFilenames()
                                            ->multiple()
                                            ->reorderable()
                                            ->appendFiles()
                                            ->openable(),
                                    ])
                                    ->columnSpanFull()
                                    ->collapsible()
                                    ->collapsed(),
                            ])
                            ->searchable()
                            ->preload(),

                        FusedGroup::make([
                            Select::make('calidad')
                                ->label('Calidad')
                                ->options([
                                    '480p' => 'CAM',
                                    '720p' => 'HD CAM',
                                    '1080p' => 'Full HD',
                                    '4k' => 'BR-S',
                                ])
                                ->native(false)
                                ->columnSpan('1')
                                ->placeholder('¿Calidad?'),
                            Select::make('idioma')
                                ->label('Idioma')
                                ->options([
                                    'lat' => 'Latino',
                                    'es' => 'Español',
                                    'sub' => 'Subtitulado',
                                    'dual' => 'Dual',
                                ])
                                ->default('lat')
                                ->native(false)
                                ->columnSpan('1')
                                ->placeholder('¿Idioma?'),
                        ])
                            ->label('Calidad e Idioma')
                            ->columnSpanFull()
                            ->columns(2),

                        TextInput::make('referencia')
                            ->label('Referencia')
                            ->url()
                            ->suffixAction(
                                Action::make('open_reference')
                                    ->icon(Heroicon::ArrowTopRightOnSquare)
                                    ->color('gray')
                                    ->url(fn(Get $get) => $get('referencia'))
                                    ->openUrlInNewTab()
                                    ->visible(fn(Get $get) => filled($get('referencia')))
                            )
                            ->placeholder('https://gnulahd.nu/'),
                    ])
                    ->addActionLabel('Nuevo Host')
                    ->orderColumn('sort')
                    ->reorderable()
                    #->compact()
                    ->columnSpanFull(),
            ]);
    }
}
