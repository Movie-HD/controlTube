<?php

namespace App\Filament\Resources\YoutubeAccountResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\YoutubeAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListYoutubeAccounts extends ListRecords
{
    protected static string $resource = YoutubeAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
