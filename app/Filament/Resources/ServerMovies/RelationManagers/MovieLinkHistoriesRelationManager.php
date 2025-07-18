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

class MovieLinkHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'movieLinkHistories';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('old_link')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('old_link'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('old_link')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('old_link')
                    ->label('Link antiguo')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Fecha de cambio')
                    ->dateTime('D, d M Y'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                #CreateAction::make(),
                #AssociateAction::make(),
            ])
            ->recordActions([
                #ViewAction::make(),
                #EditAction::make(),
                #DissociateAction::make(),
                #DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    #DissociateBulkAction::make(),
                    #DeleteBulkAction::make(),
                ]),
            ]);
    }
}
