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

class YoutubeAccountResource extends Resource
{
    protected static ?string $model = YoutubeAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Datos Cuenta')
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

                    TextInput::make('phone_number'),

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

                    Toggle::make('captcha_required'),
                    Toggle::make('verification_pending'),
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('email')->sortable()->searchable(),
                TextColumn::make('status.name')->label('Estado'),
                TextColumn::make('channel_url'),
                TextColumn::make('proxy.proxy'),
                TextColumn::make('keywords.keyword'),
                ToggleColumn::make('captcha_required'),
                ToggleColumn::make('verification_pending'),
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
