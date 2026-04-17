<?php

namespace App\Providers;

use App\Listeners\LogAccessEvents;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Failed;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Login::class => [LogAccessEvents::class],
        Logout::class => [LogAccessEvents::class],
        Failed::class => [LogAccessEvents::class],
    ];
}
