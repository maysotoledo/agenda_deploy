<?php

namespace App\Providers\Filament;

use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;
use App\Filament\Pages\Auth\ChangePassword;
use Carbon\Carbon;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login()
            ->databaseNotifications()
            ->databaseNotificationsPolling('5s')
            ->profile(ChangePassword::class) // ✅ usa página customizada para alterar senha
            ->brandName('Cartorius CFS')
            ->brandLogo(asset('images/logopjc.png'))        // ✅ logo
            ->brandLogoHeight('3.5rem')                   // ✅ altura (ajuste)
            ->favicon(asset('images/logopjc.png'))
            ->colors([
                'primary' => Color::Amber,
            ])
            // ✅ ORDEM DOS GRUPOS DO MENU
             ->navigationGroups([
                'Agenda',
                 'Informação Telemática',
                 'Análise Telemática',
             ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])

            // ❌ NÃO descubra widgets automaticamente (senão podem aparecer no Dashboard)
            // ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')

            // ✅ Somente widgets globais do painel
            ->widgets([
                AccountWidget::class,
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
            ->plugins([
                FilamentShieldPlugin::make(),
                FilamentFullCalendarPlugin::make()
                    ->selectable()
                    ->editable()
                    ->config([
                            'dayMaxEvents' => true,
                    ]),
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->bootUsing(function () {
                app()->setLocale(config('app.locale'));
                Carbon::setLocale(config('app.locale'));
        });
    }
}
