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
use Filament\Forms\Components\Repeater;
use Carbon\Carbon;
use HusamTariq\FilamentTimePicker\Forms\Components\TimePickerField;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class YoutubeAccountResource extends Resource
{
    protected static ?string $model = YoutubeAccount::class;

    protected static ?string $navigationIcon = null;

    protected static ?string $navigationLabel = '📍 Cuentas YT';

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
                        ->getOptionLabelFromRecordUsing(function ($record) {
                            $countryCode = $record->phone_country;
                            $flag = '';
                            if ($countryCode) {
                                $flag = preg_replace_callback(
                                    '/./',
                                    function ($letter) {
                                        return mb_chr(ord($letter[0]) + 127397);
                                    },
                                    strtoupper($countryCode)
                                );
                                $flag .= ' ';
                            }
                            return $flag . $record->phone_number;
                        })
                        ->searchable()
                        ->preload()
                        ->helperText(function ($state) {
                            if (!$state) return null;
                            $phoneNumber = \App\Models\PhoneNumber::find($state);
                            if (!$phoneNumber) return null;

                            $countryCode = $phoneNumber->phone_country;
                            $flag = '';
                            if ($countryCode) {
                                $flag = preg_replace_callback(
                                    '/./',
                                    function ($letter) {
                                        return mb_chr(ord($letter[0]) + 127397);
                                    },
                                    strtoupper($countryCode)
                                );
                                $flag .= ' ';
                            }

                            return $flag . $phoneNumber->phone_number;
                        })
                        ->createOptionForm([
                            PhoneInput::make('phone_number')
                                ->required()
                                ->countryStatePath('phone_country'),
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
                        ->relationship(
                            name: 'status',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn (Builder $query) => $query->orderBy('id') // Asumiendo que tienes una columna 'order' en tu tabla de estados
                        ) # Asi obtenemos la rela el nombre de la empresa.
                        ->searchable()
                        ->preload()
                        ->hiddenOn('create'), # Agregamos eso para que cargue los datos del select.

                    Select::make('proxy')
                        ->label('Proxy')
                        ->relationship(
                            name: 'proxy',
                            titleAttribute: 'proxy',
                            modifyQueryUsing: fn (Builder $query) => $query->where('in_use', false) // Filtra solo los disponibles
                        )
                        ->searchable()
                        ->preload() # Agregamos eso para que cargue los datos del select.
                        ->helperText(function ($state) {
                            if (!$state) return null;
                            $proxy = \App\Models\YoutubeProxy::find($state);
                            if (!$proxy) return null;

                            return $proxy->proxy;
                        }),

                    Select::make('resolutions')
                        ->label('Resolucion')
                        ->relationship('resolution', 'name') # Asi obtenemos la rela el nombre de la empresa.
                        ->searchable()
                        ->preload() # Agregamos eso para que cargue los datos del select.
                        ->getOptionLabelFromRecordUsing(function ($record) {
                            // Obtener la última resolución usada
                            $ultimaResolucionId = \App\Models\YoutubeAccount::latest('created_at')->first()?->resolution?->id;
                            // Si esta opción es la última usada, le agregamos una marca
                            if ($ultimaResolucionId && $record->id == $ultimaResolucionId) {
                                return '📍 ' . $record->name . ' (usado)';
                            }
                            return $record->name;
                        }),

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
                    'default' => 1, // Por defecto, usa 1 columna para pantallas pequeñas.
                    'sm' => 1, // A partir del tamaño 'sm', usa 2 columnas.
                ])
                ->schema([
                    Repeater::make('activity_times')
                        ->hiddenLabel()
                        ->schema([
                            TimePickerField::make('start_time')
                                ->label('Hora de Inicio')
                                ->okLabel("Confirm")
                                ->cancelLabel("Cancel"),
                            TimePickerField::make('end_time')
                                ->label('Hora de Fin')
                                ->okLabel("Confirm")
                                ->cancelLabel("Cancel"),
                        ])
                        ->columns(2)
                        ->defaultItems(1) // Opcional: muestra un bloque vacío al crear
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
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc') # Ordenar por fecha de creación
            ->groups([
                Tables\Grouping\Group::make('status.name')
                    ->label('Estado')
                    ->collapsible()
                    ->orderQueryUsing(fn ($query, $direction) =>
                        $query->orderBy('youtube_accounts.created_at', 'desc')
                    ),
            ])
            ->defaultGroup('status.name')
            ->groupingSettingsHidden()
            ->columns([
                TextColumn::make('name')
                    ->label('Cuenta')
                    ->formatStateUsing(function ($state, $record) {
                        return "{$state}<br><span class=\"text-xs text-gray-500\">{$record->email}</span>";
                    })
                    ->html()
                    ->searchable(['name', 'email'])
                    ->sortable(),
                TextColumn::make('phoneNumber.phone_number')
                    ->label('Teléfono')
                    ->copyable()
                    ->formatStateUsing(function ($state, $record) {
                        $countryCode = $record->phoneNumber->phone_country ?? null;
                        $flag = '';
                        if ($countryCode) {
                            $flag = preg_replace_callback(
                                '/./',
                                function ($letter) {
                                    return mb_chr(ord($letter[0]) + 127397);
                                },
                                strtoupper($countryCode)
                            );
                            $flag .= ' ';
                        }
                        return $flag . $state;
                    }),
                TextColumn::make('status.name')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Nuevo' => 'gray',
                        'Actividad sin Cuenta' => 'warning',
                        'Actividad con Cuenta' => 'info',
                        'Actividad con Verificacion 15min' => 'success',
                        'Subiendo Videos' => 'primary',
                        'Videos Subidos' => 'success',
                        'Videos Eliminados' => 'warning',
                        'Cuenta YouTube Bloqueada' => 'danger',
                        'Cuenta Google Bloqueada' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('channel_url')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('proxy.proxy')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('keywords.keyword')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('captcha_required')
                    ->label('¿Verif. Bot?')
                    ->boolean(),
                IconColumn::make('verification_pending')
                    ->label('¿Verif. 15min?')
                    ->boolean(),
                TextColumn::make('activity_times')
                    ->label('Hora de Actividad')
                    ->getStateUsing(function ($record) {
                        $activityTimes = $record->activity_times;
                        if (is_array($activityTimes)) {
                            $output = '';
                            foreach ($activityTimes as $activity) {
                                if (is_array($activity) && isset($activity['start_time']) && isset($activity['end_time'])) {
                                    try {
                                        $start_time = Carbon::parse($activity['start_time'])->format('h:i A');
                                        $end_time = Carbon::parse($activity['end_time'])->format('h:i A');
                                        $output .= "{$start_time} - {$end_time}<br>";
                                    } catch (\Exception $e) {
                                        $output .= "Error al procesar la hora<br>";
                                    }
                                } else {
                                    $output .= "Sin Horario<br>";
                                }
                            }
                            return $output;
                        }

                        return 'Sin actividades';
                    })
                    ->html(),
                TextColumn::make('pages')
                    ->label('Páginas')
                    ->formatStateUsing(function ($state, $record) {
                        // $record->pages es una colección de YoutubeAccountPage
                        return $record->pages
                            ->map(fn($accountPage) => $accountPage->page?->name)
                            ->filter()
                            ->implode(', ');
                    })
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('videos')
                    ->label('Videos')
                    ->getStateUsing(function ($record) {
                        $videos = $record->videos;
                        if ($videos->isEmpty()) {
                            return 'Sin videos';
                        }

                        $output = '';
                        foreach ($videos as $video) {
                            $colorClass = match ($video->status) {
                                'uploaded' => 'success',
                                'deleted' => 'danger',
                                'foruploaded' => 'gray',
                                default => 'gray',
                            };

                            $statusLabel = match ($video->status) {
                                'foruploaded' => 'Por Subir',
                                'uploaded' => 'Subido',
                                'deleted' => 'Eliminado',
                                default => ucfirst($video->status),
                            };

                            $output .= "<div class='mb-1'>";
                            $output .= "<span class='truncate block' style='max-width: 200px;'>{$video->video_url}</span>";
                            $output .= "<span style=\"--c-50:var(--{$colorClass}-50);--c-400:var(--{$colorClass}-400);--c-600:var(--{$colorClass}-600);\"
                                class=\"fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 min-w-[theme(spacing.6)] py-1
                                fi-color-custom bg-custom-50 text-custom-600 ring-custom-600/10 dark:bg-custom-400/10 dark:text-custom-400 dark:ring-custom-400/30 fi-color-{$colorClass}\">{$statusLabel}</span>";
                            $output .= "</div>";
                        }

                        return $output;
                    })
                    ->html()
                    ->toggleable(isToggledHiddenByDefault: true),
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
