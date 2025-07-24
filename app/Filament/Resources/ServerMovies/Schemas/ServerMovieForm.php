<?php

namespace App\Filament\Resources\ServerMovies\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Forms\Components\FileUpload;
use Filament\Actions\Action;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Schemas\Components\Section;

class ServerMovieForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->extraAttributes(['class' => 'mi-clase-personalizada'])
            ->components([
                TextInput::make('movie_name')
                    ->required(),
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
                    ])
                    ->schema([
                        TextInput::make('movie_link')
                            ->label('Enlace de la película')
                            ->required(),

                        Select::make('host_server_id')
                            ->label('Servidor Host')
                            ->relationship('hostServer', 'name')
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
                                    ->visible(fn (Get $get) => !is_null($get('host_server_id'))),
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
                    ])
                    ->addActionLabel('Nuevo Host')
                    ->reorderable(false)
                    ->columnSpanFull(),
            ]);
    }
}
