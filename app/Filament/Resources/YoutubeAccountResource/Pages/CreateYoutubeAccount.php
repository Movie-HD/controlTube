<?php

namespace App\Filament\Resources\YoutubeAccountResource\Pages;

use App\Filament\Resources\YoutubeAccountResource;
use App\Models\PhoneNumber;
use App\Models\YoutubeAccount;
use App\Models\YoutubeProxy;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class CreateYoutubeAccount extends CreateRecord
{
    protected static string $resource = YoutubeAccountResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $record = YoutubeAccount::create($data);

        if ($record->proxy_id) {
            $record->proxy->update([
                'in_use' => true,
                'used_by_account_id' => $record->id,
            ]);
        }

        # PhoneNumber
        if ($data['phone_number_id']) {
            PhoneNumber::where('id', $data['phone_number_id'])->update([
                'in_use' => true,
                'used_by_account_id' => $record->id,
            ]);
        }

        return $record;
    }

}
