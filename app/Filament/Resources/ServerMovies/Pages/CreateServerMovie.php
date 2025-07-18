<?php

namespace App\Filament\Resources\ServerMovies\Pages;

use App\Filament\Resources\ServerMovies\ServerMovieResource;
use Filament\Resources\Pages\CreateRecord;

class CreateServerMovie extends CreateRecord
{
    protected static string $resource = ServerMovieResource::class;
}
