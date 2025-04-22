<?php

namespace App\Filament\Resources;

use App\Filament\Resources\YoutubeAccountResource\Pages;
use App\Filament\Resources\YoutubeAccountResource\RelationManagers;
use App\Models\YoutubeAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput; # Agregar si es un Input [Form]
use Filament\Forms\Components\DatePicker; # Agregar si es un DatePicker [Form]
use Filament\Forms\Components\Select; # Agregar si es un Select [Form]
use Filament\Forms\Components\Toggle; # Agregar si es un Toggle [Form]
use Filament\Tables\Columns\TextColumn; # Agregar si es un Column [Table]
use Filament\Tables\Columns\ToggleColumn; # Agregar si es un Toggle [Table]
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TimePicker;

class YoutubeAccountResource extends Resource
{
    protected static ?string $model = YoutubeAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Datos Cuenta')
                ->collapsible()
                ->columns([
                    'default' => 2, // Por defecto, usa 1 columna para pantallas pequeñas.
                    'sm' => 3, // A partir del tamaño 'sm', usa 2 columnas.
                ])
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->autocomplete(false),

                    TextInput::make('email')
                        ->autocomplete(false)
                        ->datalist(['@gmail.com'])
                        ->email(),

                    TextInput::make('password')
                        #->password()
                        #->revealable()
                        ->label('Contraseña'),

                    Select::make('phone_number_id')
                        ->label('Número de Teléfono')
                        ->relationship(
                            name: 'phoneNumber',
                            titleAttribute: 'phone_number',
                            modifyQueryUsing: fn (Builder $query) => $query->where('in_use', false)
                        )
                        ->searchable()
                        ->preload()
                        ->createOptionForm([
                            TextInput::make('phone_number')->required(),
                            Toggle::make('is_physical_chip')
                                ->label('¿Chip físico?')
                                ->reactive(),

                            TextInput::make('name')
                                ->label('Nombre')
                                ->visible(fn ($get) => $get('is_physical_chip')),

                            TextInput::make('dni')
                                ->label('DNI')
                                ->visible(fn ($get) => $get('is_physical_chip')),

                            TextInput::make('iccid_code')
                                ->label('Código ICCID')
                                ->visible(fn ($get) => $get('is_physical_chip'))
                                ->suffixAction(
                                    Forms\Components\Actions\Action::make('scan_qr')
                                        ->icon('heroicon-o-qr-code')
                                        ->label('Escanear')
                                        ->modalHeading('Escanear código QR')
                                        ->modalDescription('Coloca el código QR frente a la cámara para escanearlo')
                                        ->modalContent(view ('filament.components.qr-scanner'))
                                        ->modalSubmitActionLabel('Cerrar')
                                        ->modalWidth('md')
                                ),

                            DatePicker::make('registered_at')
                                ->label('Fecha de Registro'),

                            Toggle::make('in_use')
                                ->label('En Uso'),
                        ]),

                    DatePicker::make('birth_date'),

                    Select::make('gender')
                        ->label('Género')
                        ->options([
                            'male' => 'Masculino',
                            'female' => 'Femenino',
                            'other' => 'Otro',
                        ]),
                ]),

                Section::make('Datos de estado')
                ->collapsible()
                ->columns([
                    'default' => 2, // Por defecto, usa 1 columna para pantallas pequeñas.
                    'sm' => 3, // A partir del tamaño 'sm', usa 2 columnas.
                ])
                ->schema([
                    Select::make('status')
                        ->label('status')
                        ->relationship('status', 'name') # Asi obtenemos la rela el nombre de la empresa.
                        ->searchable()
                        ->preload()
                        ->required(), # Agregamos eso para que cargue los datos del select.

                    Select::make('proxy')
                        ->label('Proxy')
                        ->relationship(
                            name: 'proxy',
                            titleAttribute: 'proxy',
                            modifyQueryUsing: fn (Builder $query) => $query->where('in_use', false) // Filtra solo los disponibles
                        )
                        ->searchable()
                        ->preload(), # Agregamos eso para que cargue los datos del select.

                    Select::make('resolutions')
                        ->label('Resolucion')
                        ->relationship('resolution', 'name') # Asi obtenemos la rela el nombre de la empresa.
                        ->searchable()
                        ->preload(), # Agregamos eso para que cargue los datos del select.

                    TextInput::make('channel_url')
                        ->url()
                        ->nullable(),

                    ToggleButtons::make('captcha_required')
                        ->label('¿Pidio Verificacion para crear la Cuenta?')
                        ->inline()
                        ->boolean(),
                    ToggleButtons::make('verification_pending')
                        ->label('¿Verificación 15min?')
                        ->inline()
                        ->boolean(),
                ]),

                # Seccion Hora de Actividad
                Section::make('Hora de Actividad')
                ->collapsible()
                ->columns([
                    'default' => 2, // Por defecto, usa 1 columna para pantallas pequeñas.
                    'sm' => 3, // A partir del tamaño 'sm', usa 2 columnas.
                ])
                ->schema([
                    TimePicker::make('start_time')
                                ->label('Hora de Inicio')
                                ->seconds(false)
                                ->closeOnDateSelection(),

                    TimePicker::make('end_time')
                                ->label('Hora de Fin')
                                ->seconds(false),
                ]),

                # Seccion Notas Adicionales
                Section::make('Notas Adicionales')
                ->collapsible()
                ->collapsed(fn ($livewire) => $livewire->getRecord() === null)
                ->columns([
                    'default' => 2, // Por defecto, usa 1 columna para pantallas pequeñas.
                    'sm' => 3, // A partir del tamaño 'sm', usa 2 columnas.
                ])
                ->schema([
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
                        ->hiddenOn('create')
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc') # Ordenar por fecha de creación
            ->columns([
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('email')->sortable()->searchable(),
                TextColumn::make('status.name')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Nuevo' => 'gray',
                        'approved' => 'success',
                        'decline' => 'danger',
                    }),
                TextColumn::make('channel_url')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('proxy.proxy')
                    ->copyable(),
                TextColumn::make('keywords.keyword')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('captcha_required')
                    ->label('¿Verif. Bot?')
                    ->boolean(),
                IconColumn::make('verification_pending')
                    ->label('¿Verif. 15min?')
                    ->boolean(),
                TextColumn::make('start_time')
                    ->label('Hora de Inicio')
                    ->dateTime('h:i A')
                    ->copyable(),
                TextColumn::make('end_time')
                    ->label('Hora de Fin')
                    ->Time('h:i A'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\KeywordsRelationManager::class,
            RelationManagers\VideosRelationManager::class,
            RelationManagers\PagesRelationManager::class,
            # php artisan make:filament-relation-manager NombreResource NombreMetodoRelacion CampoRelacion
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListYoutubeAccounts::route('/'),
            'create' => Pages\CreateYoutubeAccount::route('/create'),
            'edit' => Pages\EditYoutubeAccount::route('/{record}/edit'),
        ];
    }
}
