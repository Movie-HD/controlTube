<?php

namespace App\Filament\Resources\Notes\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\FusedGroup;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Infolists\Components\TextEntry;
use Filament\Forms\Components\Placeholder;

class NoteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->columnSpan('full'),
                Section::make('Descripción y Tags')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        FusedGroup::make([
                            RichEditor::make('description'),
                            TagsInput::make('tag'),
                        ]),
                    ])
                    ->columnSpan('full'),
                Repeater::make('noteDetails')
                    ->extraAttributes(['class' => 'mi-clase-personalizada'])
                    ->relationship()
                    ->schema([
                        Placeholder::make('dynamic_title')
                            ->hiddenLabel()
                            ->content(fn ($get) => ($get('name') ?? 'Nuevo'))
                            ->reactive()
                            ->extraAttributes(['class' => 'fi-header-heading fi-size-lg TextEntry']),
                        Section::make('Contenido')
                            ->collapsible()
                            ->collapsed()
                            ->schema([
                                FusedGroup::make([
                                    TextInput::make('name')
                                        ->placeholder('Nombre'),
                                    RichEditor::make('description')
                                        ->json()
                                        ->placeholder('Descripción'),
                                    CodeEditor::make('content')
                                        ->language(Language::Php),
                                ]),
                                FileUpload::make('screenshot')
                                    ->multiple()
                                    ->image()
                                    ->imageEditor()
                                    ->openable()
                                    ->downloadable(),
                            ]),
                    ])
                    ->columnSpan('full')
                    ->hiddenLabel()
                    ->collapsible()
                    ->addActionLabel('Nuevo Detalle')
                    ->reorderable(false)

            ]);
    }
}
