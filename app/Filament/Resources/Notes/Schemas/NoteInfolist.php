<?php

namespace App\Filament\Resources\Notes\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\CodeEntry;
use Phiki\Grammar\Grammar;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class NoteInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('description'),
                TextEntry::make('tag'),
                RepeatableEntry::make('noteDetails')
                    ->schema([
                        TextEntry::make('name'),
                        CodeEntry::make('content')
                            ->grammar(Grammar::Php),
                        TextEntry::make('screenshot'),
                    ])
                    ->columnSpan('full')
                    ->label('Detalles'),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
                TextEntry::make('deleted_at')
                    ->dateTime(),
            ]);
    }
}
