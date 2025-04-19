<?php

namespace App\Filament\Resources\YoutubeAccountResource\Pages;

use App\Filament\Resources\YoutubeAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use App\Models\YoutubeProxy;
use Illuminate\Database\Eloquent\Model;

class EditYoutubeAccount extends EditRecord
{
    protected static string $resource = YoutubeAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Obtenemos los valores originales antes de la actualización
        $originalProxyId = $record->getOriginal('proxy_id');
        $originalPhoneNumberId = $record->getOriginal('phone_number_id');

        // Actualizamos el registro
        $record->update($data);

        $newProxyId = $record->proxy_id;
        $newPhoneNumberId = $record->phone_number_id;

        // === PROXY ===
        if ($originalProxyId && $originalProxyId !== $newProxyId) {
            DB::table('youtube_proxies')->where('id', $originalProxyId)->update([
                'in_use' => false,
                'used_by_account_id' => null,
            ]);
        }

        if ($newProxyId) {
            DB::table('youtube_proxies')->where('id', $newProxyId)->update([
                'in_use' => true,
                'used_by_account_id' => $record->id,
            ]);
        }

        // === PHONE NUMBER ===
        if ($originalPhoneNumberId && $originalPhoneNumberId !== $newPhoneNumberId) {
            DB::table('phone_numbers')->where('id', $originalPhoneNumberId)->update([
                'in_use' => false,
                'used_by_account_id' => null,
            ]);
        }

        if ($newPhoneNumberId) {
            DB::table('phone_numbers')->where('id', $newPhoneNumberId)->update([
                'in_use' => true,
                'used_by_account_id' => $record->id,
            ]);
        }

        return $record;
    }

}
