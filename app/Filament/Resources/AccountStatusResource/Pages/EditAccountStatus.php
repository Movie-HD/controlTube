<?php

namespace App\Filament\Resources\AccountStatusResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\AccountStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAccountStatus extends EditRecord
{
    protected static string $resource = AccountStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
