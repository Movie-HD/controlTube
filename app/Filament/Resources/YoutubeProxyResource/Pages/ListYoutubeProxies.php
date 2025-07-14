<?php

namespace App\Filament\Resources\YoutubeProxyResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use App\Filament\Resources\YoutubeProxyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\Textarea;
use App\Models\YoutubeProxy;
use Filament\Notifications\Notification;

class ListYoutubeProxies extends ListRecords
{
    protected static string $resource = YoutubeProxyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('importProxies')
                ->label('Importar Proxies')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->size('lg')
                ->outlined()
                ->modalSubmitAction(
                    fn (Action $action) => $action->label('Importar')->color('primary')
                )
                ->schema([
                    Textarea::make('proxies_list')
                        ->label('Lista de Proxies')
                        ->placeholder('Pega aquí la lista de proxies, cada uno en una línea.')
                        ->rows(5),
                ])
                ->action(function (array $data) {
                    $proxies = explode("\n", $data['proxies_list']);
                    $count = 0;
                    foreach ($proxies as $proxy) {
                        $proxy = trim($proxy);
                        if (!empty($proxy)) {
                            YoutubeProxy::create(['proxy' => $proxy]);
                            $count++;
                        }
                    }
                    Notification::make()
                        ->success()
                        ->title('Proxies importados')
                        ->body("Se importaron {$count} proxies correctamente")
                        ->send();
                }),
        ];
    }
}
