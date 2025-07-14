<?php

namespace App\Filament\Resources\YoutubePageResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\YoutubePageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditYoutubePage extends EditRecord
{
    protected static string $resource = YoutubePageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
