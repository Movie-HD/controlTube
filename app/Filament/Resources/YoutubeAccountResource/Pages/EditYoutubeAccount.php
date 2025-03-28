<?php

namespace App\Filament\Resources\YoutubeAccountResource\Pages;

use App\Filament\Resources\YoutubeAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditYoutubeAccount extends EditRecord
{
    protected static string $resource = YoutubeAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
