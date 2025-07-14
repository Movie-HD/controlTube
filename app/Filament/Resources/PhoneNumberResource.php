<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Schemas\Components\Section;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\PhoneNumberResource\Pages\ListPhoneNumbers;
use App\Filament\Resources\PhoneNumberResource\Pages\CreatePhoneNumber;
use App\Filament\Resources\PhoneNumberResource\Pages\EditPhoneNumber;
use App\Filament\Resources\PhoneNumberResource\Pages;
use App\Filament\Resources\PhoneNumberResource\RelationManagers;
use App\Models\PhoneNumber;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;

class PhoneNumberResource extends Resource
{
    protected static ?string $model = PhoneNumber::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string | \UnitEnum | null $navigationGroup = 'Configuracion';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                PhoneInput::make('phone_number')
                    ->label('Número de Teléfono')
                    ->countryStatePath('phone_country')
                    ->required()
                    ->defaultCountry('US') // Establecemos España como país por defecto
                    ->displayNumberFormat(PhoneInputNumberType::INTERNATIONAL), // Formato internacional para mejor visualización
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
                        Action::make('scan_qr')
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
        ->columns([
            TextColumn::make('phone_number')
                ->label('Número de Teléfono')
                ->searchable()
                ->sortable(),
                TextColumn::make('phone_country')
                ->label('País')
                ->formatStateUsing(function ($state) {
                    if (!$state) return '';

                    $countryCode = strtoupper($state);
                    return preg_replace_callback(
                        '/./',
                        function ($letter) {
                            return mb_chr(ord($letter[0]) + 127397);
                        },
                        $countryCode
                    );
                }),

            TextColumn::make('usedByAccount.name') # TextColumn::make('NombreDelMetodo.NombreDelCampo')
                ->color('success')
                ->icon('heroicon-o-check')
                ->label('Usado por:')
                ->searchable()
                ->sortable(),

            IconColumn::make('in_use')
                ->label('¿En Uso?')
                ->boolean(),

            IconColumn::make('is_physical_chip')
                ->label('Chip Físico')
                ->boolean(),

            TextColumn::make('name')
                ->label('Nombre')
                ->searchable()
                ->toggleable(),

            TextColumn::make('dni')
                ->label('DNI')
                ->toggleable(),

            TextColumn::make('iccid_code')
                ->label('Código ICCID')
                ->toggleable(),

            TextColumn::make('registered_at')
                ->label('Fecha de Registro')
                ->dateTime()
                ->sortable()
                ->toggleable(),

            TextColumn::make('created_at')
                ->label('Creado')
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
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPhoneNumbers::route('/'),
            'create' => CreatePhoneNumber::route('/create'),
            'edit' => EditPhoneNumber::route('/{record}/edit'),
        ];
    }
}
