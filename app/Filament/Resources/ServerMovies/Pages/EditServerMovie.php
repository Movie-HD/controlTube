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
}
