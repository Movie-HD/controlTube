<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class MovieScraperPage extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-film';

    protected string $view = 'filament.pages.movie-scraper-page';

    protected static ?string $navigationLabel = 'Movie Scraper';

    protected static ?string $title = ''; # Ocultamos el título "Extractor de Películas"

    #protected static ?string $navigationGroup = 'Configuracion';

    protected static ?int $navigationSort = 1;
}
