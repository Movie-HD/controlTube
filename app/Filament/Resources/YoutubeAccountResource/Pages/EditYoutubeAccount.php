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
    // Obtenemos el ID del proxy original directamente desde la base de datos (sin el cambio aún aplicado)
    $originalProxyId = $record->getOriginal('proxy_id');

    // Actualizamos el registro
    $record->update($data);

    $newProxyId = $record->proxy_id;

    // Si el proxy cambió, liberamos el anterior
    if ($originalProxyId && $originalProxyId !== $newProxyId) {
        DB::table('youtube_proxies')->where('id', $originalProxyId)->update([
            'in_use' => false,
            'used_by_account_id' => null,
        ]);
    }

    // Asignamos el nuevo si existe
    if ($newProxyId) {
        DB::table('youtube_proxies')->where('id', $newProxyId)->update([
            'in_use' => true,
            'used_by_account_id' => $record->id,
        ]);
    }

    return $record;
}

}
