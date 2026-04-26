<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('queue:agenda-worker', function () {
    $this->call('queue:work', [
        'connection' => config('queue.default'),
        '--queue' => 'default',
        '--tries' => 3,
        '--timeout' => 300,
        '--sleep' => 1,
    ]);
})->purpose('Inicia o worker permanente da fila para jobs da aplicacao');
