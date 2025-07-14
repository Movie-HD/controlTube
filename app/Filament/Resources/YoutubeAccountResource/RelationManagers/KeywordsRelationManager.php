<?php

namespace App\Filament\Resources\YoutubeAccountResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Notifications\Notification;

class KeywordsRelationManager extends RelationManager
{
    protected static string $relationship = 'keywords';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('keyword')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('keyword')
            ->columns([
                TextColumn::make('keyword'),
                ToggleColumn::make('used'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
                Action::make('importKeywords')
                    ->label('Importar Keywords')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->size('lg')
                    ->outlined()
                    #->modalSubmitActionLabel('Confirmar Importación') // Cambiar texto del botón principal
                    ->modalSubmitAction(fn (Action $action) => $action
                        ->label('Importar')
                        ->color('primary')
                    )
                    ->modalCancelAction(fn (Action $action) => $action
                        ->label('Cancelar')
                        ->color('danger')
                    )
                    ->schema([
                        Textarea::make('keywords_list')
                            ->label('Lista de Keywords')
                            ->placeholder('Pega aquí la lista de keywords, cada una en una línea.')
                            ->rows(5),
                    ])
                    ->action(function (array $data, $livewire) {
                        $keywords = explode("\n", $data['keywords_list']);
                        $count = 0;
                        foreach ($keywords as $keyword) {
                            $keyword = trim($keyword);
                            if (!empty($keyword)) {
                                $livewire->getOwnerRecord()->keywords()->create(['keyword' => $keyword]);
                                $count++;
                            }
                        }
                        Notification::make()
                            ->success()
                            ->title('Keywords importadas')
                            ->body("Se importaron {$count} keywords correctamente")
                            ->send();
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
