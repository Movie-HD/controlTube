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

class AssociatedWebRelationManager extends RelationManager
{
    protected static string $relationship = 'associatedWeb';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('link')
                    ->required(),
                Toggle::make('was_updated')
                    ->default(true),
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
                ToggleColumn::make('was_updated'),
            ])
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
