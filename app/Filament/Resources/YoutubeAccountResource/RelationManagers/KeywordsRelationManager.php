<?php

namespace App\Filament\Resources\YoutubeAccountResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
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

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('keyword')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('keyword')
            ->columns([
                Tables\Columns\TextColumn::make('keyword'),
                ToggleColumn::make('used'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                Tables\Actions\Action::make('importKeywords')
                    ->label('Importar Keywords')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->size('lg')
                    ->outlined()
                    #->modalSubmitActionLabel('Confirmar Importación') // Cambiar texto del botón principal
                    ->modalSubmitAction(fn (\Filament\Actions\StaticAction $action) => $action
                        ->label('Importar')
                        ->color('primary')
                    )
                    ->modalCancelAction(fn (\Filament\Actions\StaticAction $action) => $action
                        ->label('Cancelar')
                        ->color('danger')
                    )
                    ->form([
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
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
