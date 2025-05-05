<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use App\Models\NavigationLink;
use Filament\Navigation\NavigationItem;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use App\Filament\Pages\MovieScraperPage;

class DashboardPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->maxContentWidth('full')
            ->id('dashboard')
            ->path('dashboard')
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => Blade::render('<style>.mdtp__wrapper { top: calc(50% - 212px); }</style>')
                )
            ->login()
            ->registration()
            ->topNavigation()
            ->breadcrumbs(false)
            ->brandName('Control YT')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                MovieScraperPage::class,
                //Pages\Dashboard::class,
            ])
            ->navigationItems([
                ...$this->getCustomNavigationItems(),
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    private function getCustomNavigationItems(): array
    {
        try {
            return NavigationLink::with(['group' => function ($query) {
                    $query->orderBy('sort_order');
                }])
                ->where('is_active', true)
                ->whereHas('group', function ($query) {
                    $query->where('is_active', true);
                })
                ->orderBy('sort_order')
                ->get()
                ->sortBy(['group.sort_order', 'sort_order'])
                ->map(function ($link) {
                    return NavigationItem::make($link->name)
                        ->url($link->url, shouldOpenInNewTab: $link->open_in_new_tab)
                        ->icon($link->icon)
                        ->group($link->group->name)
                        ->sort($link->sort_order);
                })
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }
}
