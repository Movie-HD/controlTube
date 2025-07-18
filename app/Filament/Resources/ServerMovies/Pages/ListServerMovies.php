<?php

namespace App\Filament\Resources\ServerMovies\Pages;

use App\Filament\Resources\ServerMovies\ServerMovieResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListServerMovies extends ListRecords
{
    protected static string $resource = ServerMovieResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
