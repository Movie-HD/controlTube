<?php

namespace App\Filament\Resources\ServerMovies\Pages;

use App\Filament\Resources\ServerMovies\ServerMovieResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditServerMovie extends EditRecord
{
    protected static string $resource = ServerMovieResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record->id]); # . '?activeRelationManager=0'
    }
}
