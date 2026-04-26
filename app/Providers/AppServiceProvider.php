<?php

namespace App\Providers;

use App\Models\Evento;
use App\Observers\EventoObserver;
use App\Policies\EventoPolicy;
use App\Services\Queue\QueueHealthService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Queue\Events\Looping;
use Symfony\Component\Mime\Address;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Evento::observe(EventoObserver::class);
        Gate::policy(Evento::class, EventoPolicy::class);

        $this->app['events']->listen(MessageSending::class, function (MessageSending $event): void {
            Log::channel('agenda_mail')->info('Laravel iniciou envio SMTP.', [
                'mailer' => $event->data['mailer'] ?? config('mail.default'),
                'subject' => $event->message?->getSubject(),
                'to' => array_map(
                    fn (Address $address) => $address->getAddress(),
                    array_values($event->message?->getTo() ?? []),
                ),
            ]);
        });

        $this->app['events']->listen(MessageSent::class, function (MessageSent $event): void {
            Log::channel('agenda_mail')->info('Laravel confirmou envio ao transport.', [
                'mailer' => $event->data['mailer'] ?? config('mail.default'),
                'subject' => $event->message?->getSubject(),
                'to' => array_map(
                    fn (Address $address) => $address->getAddress(),
                    array_values($event->message?->getTo() ?? []),
                ),
            ]);
        });

        $this->app['events']->listen(Looping::class, function (): void {
            app(QueueHealthService::class)->touchHeartbeat();
        });
    }
}
