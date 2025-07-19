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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        # Solo ejecutamos si cambia el link
        if ($this->record->movie_link !== $data['movie_link'] && $this->record->movie_link !== null) {

            # Guardamos historial
            \App\Models\MovieLinkHistory::create([
                'server_movie_id' => $this->record->id,
                'old_link' => $this->record->movie_link,
            ]);

            # Actualizamos todas las webs asociadas a false
            $this->record->associatedWeb()->update([
                'was_updated' => false,
            ]);
        }
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record->id]); # . '?activeRelationManager=0'
    }
}
