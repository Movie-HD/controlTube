<?php

namespace App\Providers\Filament;

use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Exception;
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
use Filament\Support\Facades\FilamentView;

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
                AccountWidget::class,
                FilamentInfoWidget::class,
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

    public function boot(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::PAGE_START, # Puedes usar otros hooks según dónde quieres el CSS
            fn (): string => '<style>
                main.fi-main {
                    padding: clamp(2px, calc(2px + .8vw), 15px);
                }
                html.fi.dark {--_is-dark:true}
                .mi-clase-personalizada {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(min(calc(50% - 3px), calc(120px + 10vw)), 1fr));

                    /* Nueva propiedad if() en CSS, solo acpeta style pero mas adelante soportara selector directamente las clases con :has()*/
                    background-color: if(
                        style(--_is-dark: true): #ff000059;
                        else: #7f00ff5c
                    );

                    gap: clamp(2px, calc(2px + .8vw), 15px);
                    padding: clamp(2px, calc(2px + .8vw), 15px);
                    border-radius: clamp(2px, calc(2px + .8vw), 15px);
                }
            </style>',
            scopes: [\App\Filament\Resources\AccountStatusResource::class]
        );
        FilamentView::registerRenderHook(
            PanelsRenderHook::PAGE_START, # Puedes usar otros hooks según dónde quieres el CSS
            fn (): string => '<style>
                main.fi-main {
                    padding: clamp(2px, calc(2px + .8vw), 15px);
                }
                html.fi.dark {--_is-dark:true}
                .mi-clase-personalizada td{
                    align-content: end;
                }
                .mi-clase-td td:nth-child(2) .fi-fo-field-label-ctn{
                    justify-content: center;
                }
                .fi-dropdown-list-item:not(.fi-disabled):not([disabled]) {
                    cursor: pointer;
                }
                .fi-disabled {
                    opacity: 0.3;
                }
            </style>',
            scopes: [
                \App\Filament\Resources\ServerMovies\ServerMovieResource::class,
                \App\Filament\Resources\ServerMovies\RelationManagers\AssociatedWebRelationManager::class,
            ]
        );
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
        } catch (Exception $e) {
            return [];
        }
    }
}
