<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use App\Models\NavigationGroup;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\NavigationLinkResource\Pages\ListNavigationLinks;
use App\Filament\Resources\NavigationLinkResource\Pages\CreateNavigationLink;
use App\Filament\Resources\NavigationLinkResource\Pages\EditNavigationLink;
use App\Filament\Resources\NavigationLinkResource\Pages;
use App\Models\NavigationLink;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;

class NavigationLinkResource extends Resource
{
    protected static ?string $model = NavigationLink::class;
    protected static string | \BackedEnum | null $navigationIcon = null;
    protected static ?string $modelLabel = '🔗 Marcador';
    // $modelLabel define la etiqueta en singular que se usará para referirse a una sola instancia del modelo en la interfaz de Filament.
        # Por ejemplo, cuando el sistema muestra mensajes como "Crear Marcador" o "Editar Marcador", usará el valor de $modelLabel.

    protected static ?string $pluralModelLabel = '🔗 Marcadores';
    // $pluralModelLabel define la etiqueta en plural para referirse a varias instancias del modelo.
        # Por ejemplo, en la página de listado o en menús, verás textos como "Lista de Marcadores" o "Todos los Marcadores", usando el valor de $pluralModelLabel.

    protected static string | \UnitEnum | null $navigationGroup = 'Configuracion';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required(),
                TextInput::make('url')
                    ->label('URL')
                    ->required()
                    ->url(),
                TextInput::make('icon')
                    ->label('Icono')
                    ->default('heroicon-o-link')
                    ->required(),
                Select::make('group_id')
                    ->label('Grupo')
                    ->relationship('group', 'name')
                    ->hintAction(
                        Action::make('createPhoneNumber')
                            ->label('Crear')
                            ->icon('heroicon-m-plus')
                            ->modalHeading('Nuevo Grupo')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nombre')
                                    ->required(),
                                Select::make('color')
                                    ->label('Color')
                                    ->options([
                                        'primary' => 'Principal',
                                        'success' => 'Verde',
                                        'warning' => 'Amarillo',
                                        'danger' => 'Rojo',
                                        'info' => 'Azul',
                                        'gray' => 'Gris',
                                    ])
                                    ->default('primary')
                                    ->required(),
                                TextInput::make('sort_order')
                                    ->label('Orden')
                                    ->numeric()
                                    ->default(0),
                                Toggle::make('is_active')
                                    ->label('Activo')
                                    ->default(true),
                            ])
                            ->action(function (array $data, Set $set) {
                                $new = NavigationGroup::create($data);
                                // ✅ Actualiza el select con la nueva opción
                                $set('group_id', $new->id);
                            })
                    )
                    ->editOptionForm([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required(),
                        Select::make('color')
                            ->label('Color')
                            ->options([
                                'primary' => 'Principal',
                                'secondary' => 'Secundario',
                                'success' => 'Verde',
                                'warning' => 'Amarillo',
                                'danger' => 'Rojo',
                                'info' => 'Azul',
                                'gray' => 'Gris',
                            ])
                            ->default('primary')
                            ->required(),
                        TextInput::make('sort_order')
                            ->label('Orden')
                            ->numeric()
                            ->default(0),
                        Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true),
                    ])
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('sort_order')
                    ->label('Orden')
                    ->numeric()
                    ->default(0),
                Toggle::make('open_in_new_tab')
                    ->label('Abrir en nueva pestaña')
                    ->default(true),
                Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true),

                Section::make('Notas Adicionales')
                    ->collapsible()
                    ->collapsed(fn ($livewire) => $livewire->getRecord() === null)
                    ->columns([
                        'default' => 2, // Por defecto, usa 1 columna para pantallas pequeñas.
                        'sm' => 3, // A partir del tamaño 'sm', usa 2 columnas.
                    ])
                    ->schema([
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->columnSpan(1),
                        TextInput::make('password')
                            ->label('Contraseña')
                            ->columnSpan(1),
                        RichEditor::make('descripcion')
                        ->columnSpan(2)
                        ->label('Descripción')
                        ->nullable()
                        ->toolbarButtons([
                            'attachFiles',
                            'blockquote',
                            'bold',
                            'bulletList',
                            'codeBlock',
                            'h2',
                            'h3',
                            'italic',
                            'link',
                            'orderedList',
                            'redo',
                            'strike',
                            'undo',
                        ]),
                        FileUpload::make('screenshots')
                            ->label('Adjuntar Archivos')
                            ->preserveFilenames()
                            ->multiple()
                            ->reorderable()
                            ->appendFiles()
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginationPageOptions([20, 50, 100, 'all'])
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                TextColumn::make('url')
                    ->label('URL')
                    ->limit(60)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 60) {
                            return null;
                        }
                        return $state;
                    }),
                TextColumn::make('group.name')
                    ->label('Grupo')
                    ->searchable()
                    ->badge()
                    ->color(fn (NavigationLink $record): string => $record->group?->color ?? 'gray'),
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label('Orden')
                    ->sortable(),
            ])
            ->groups([
                Group::make('group.name') // ✅ Agrupa por el nombre
                    ->label('Grupo')
                    ->collapsible()
                    ->orderQueryUsing(fn ($query, $direction) =>
                        $query->orderBy('navigation_links.group_id', 'asc')
                    ),
            ])
            ->defaultGroup('group.name')
            ->groupingSettingsHidden()
            ->filters([
                SelectFilter::make('group_id')
                    ->label('Grupo')
                    ->relationship('group', 'name'),
                TernaryFilter::make('is_active')
                    ->label('Activo'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNavigationLinks::route('/'),
            'create' => CreateNavigationLink::route('/create'),
            'edit' => EditNavigationLink::route('/{record}/edit'),
        ];
    }
}
