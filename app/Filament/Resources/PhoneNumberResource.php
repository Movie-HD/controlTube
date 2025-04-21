<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PhoneNumberResource\Pages;
use App\Filament\Resources\PhoneNumberResource\RelationManagers;
use App\Models\PhoneNumber;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;

class PhoneNumberResource extends Resource
{
    protected static ?string $model = PhoneNumber::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPhoneNumbers::route('/'),
            'create' => Pages\CreatePhoneNumber::route('/create'),
            'edit' => Pages\EditPhoneNumber::route('/{record}/edit'),
        ];
    }
}
