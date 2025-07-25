<?php

namespace App\Filament\Resources\YoutubeProxyResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\YoutubeProxyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditYoutubeProxy extends EditRecord
{
    protected static string $resource = YoutubeProxyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
