<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NavigationLinkResource\Pages;
use App\Models\NavigationLink;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Set;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;

class NavigationLinkResource extends Resource
{
    protected static ?string $model = NavigationLink::class;
    protected static ?string $navigationIcon = 'heroicon-o-link';
    protected static ?string $modelLabel = 'Enlace de Navegación';
    protected static ?string $pluralModelLabel = 'Enlaces de Navegación';
    protected static ?string $navigationGroup = 'Configuracion';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required(),
                Forms\Components\TextInput::make('url')
                    ->label('URL')
                    ->required()
                    ->url(),
                Forms\Components\TextInput::make('icon')
                    ->label('Icono')
                    ->default('heroicon-o-link')
                    ->required(),
                Forms\Components\Select::make('group_id')
                    ->label('Grupo')
                    ->relationship('group', 'name')
                    ->hintAction(
                        Action::make('createPhoneNumber')
                            ->label('Crear')
                            ->icon('heroicon-m-plus')
                            ->modalHeading('Nuevo Grupo')
                            ->form([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre')
                                    ->required(),
                                Forms\Components\Select::make('color')
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
                                Forms\Components\TextInput::make('sort_order')
                                    ->label('Orden')
                                    ->numeric()
                                    ->default(0),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Activo')
                                    ->default(true),
                            ])
                            ->action(function (array $data, Set $set) {
                                $new = \App\Models\NavigationGroup::create($data);
                                // ✅ Actualiza el select con la nueva opción
                                $set('group_id', $new->id);
                            })
                    )
                    ->editOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required(),
                        Forms\Components\Select::make('color')
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
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Orden')
                            ->numeric()
                            ->default(0),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true),
                    ])
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('sort_order')
                    ->label('Orden')
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('open_in_new_tab')
                    ->label('Abrir en nueva pestaña')
                    ->default(true),
                Forms\Components\Toggle::make('is_active')
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
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('password')
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
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('url')
                    ->label('URL'),
                Tables\Columns\TextColumn::make('group.name')
                    ->label('Grupo')
                    ->searchable()
                    ->badge()
                    ->color(fn (NavigationLink $record): string => $record->group?->color ?? 'gray'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Orden')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('group_id')
                    ->label('Grupo')
                    ->relationship('group', 'name'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Activo'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNavigationLinks::route('/'),
            'create' => Pages\CreateNavigationLink::route('/create'),
            'edit' => Pages\EditNavigationLink::route('/{record}/edit'),
        ];
    }
}
