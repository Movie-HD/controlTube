<?php

namespace App\Filament\Resources\AccountStatusResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\AccountStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAccountStatuses extends ListRecords
{
    protected static string $resource = AccountStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
