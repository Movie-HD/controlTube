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
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Html;

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
                            ->reactive()
                            ->extraAttributes(['class' => 'fi-header-heading fi-size-lg TextEntry'])
                            ->afterLabel(Html::make(<<<'HTML'
                                    <p x-bind:style="`background-color: ${$get('color')}`" class="rebelde"></p>
                                    <p x-text="$get('name')" class="rebeldeTitle"></p>
                                    <style>
                                        li.fi-fo-repeater-item {
                                            position: relative;
                                        }
                                        .fi-fo-repeater-item-header:after {
                                            font-size:13px;
                                            z-index: 2;
                                        }
                                        ul.fi-fo-repeater-item-header-start-actions, ul.fi-fo-repeater-item-header-end-actions{
                                            z-index: 2;
                                        }
                                        .rebeldeTitle, .rebelde {
                                            padding: 5px;
                                            position: absolute;
                                            margin-top: -45px;
                                            width: calc(100% - 185px);
                                            height: 44px;
                                            margin-left: 44px;
                                            font-weight: bold;
                                            font-size: calc(13px + .4vw);
                                            overflow: hidden;
                                            text-overflow: ellipsis;
                                            white-space: nowrap;
                                            line-height: 1.8;
                                            color: if(
                                                style(--_is-dark: true): white;
                                                else: black;
                                            );
                                            z-index: 1;
                                            @media (width <= 1023px) {
                                                width: calc(100% - 180px);
                                            }
                                        }
                                        .rebelde {
                                            width: 100%;
                                            left: 0;
                                            top: 0;
                                            margin: 0;
                                            padding: 0;
                                            border-top-left-radius: 5px;
                                            border-top-right-radius: 5px;
                                            padding-left: 45px;                                            
                                            z-index: 1;
                                        }
                                    </style>
                                HTML)),
                        
                        Section::make('Contenido')
                            ->collapsible()
                            ->collapsed()
                            ->schema([
                                FusedGroup::make([
                                    FusedGroup::make([
                                        TextInput::make('name')
                                            ->placeholder('Nombre')
                                            ->reactive()
                                            ->debounce(500)
                                            ->columnSpan(['default' => 9, 'sm' => 10, 'md' => 11]),
                                        Select::make('color')
                                            ->options([
                                                '#db1717' => 'Rojo',
                                                '#038e5b' => 'Verde',
                                                '#004aa4' => 'Azul',
                                                '#c28c00' => 'Amarillo',
                                                '#ff6800' => 'Naranja',
                                            ])
                                            ->native(false)
                                            ->columnSpan(['default' => 5, 'sm' => 4, 'md' => 3]),
                                    ])
                                    ->columns(['default' => 14, 'sm' => 14, 'md' => 14]),
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
                    ->reorderable()

            ]);
    }
}
