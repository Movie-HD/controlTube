<?php

namespace App\Filament\Resources\YoutubePageResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\YoutubePageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListYoutubePages extends ListRecords
{
    protected static string $resource = YoutubePageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
