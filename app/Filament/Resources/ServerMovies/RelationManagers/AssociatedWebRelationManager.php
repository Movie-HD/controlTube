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

class AssociatedWebRelationManager extends RelationManager
{
    protected static string $relationship = 'associatedWebs';

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
                                modifyQueryUsing: fn (Builder $query) => $query->where('server_movie_id', $this->getOwnerRecord()->id)
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
            ])
            ->groups([
                Group::make('get_domain')
                    ->label('Dominio')
                    ->collapsible()
                    ->orderQueryUsing(fn ($query, $direction) =>
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
                CreateAction::make(),
                #AssociateAction::make(),
            ])
            ->recordActions([
                #ViewAction::make(),
                EditAction::make(),
                #DissociateAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
