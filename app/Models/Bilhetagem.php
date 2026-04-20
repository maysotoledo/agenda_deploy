<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bilhetagem extends Model
{
    protected $table = 'bilhetagens';

    protected $fillable = [
        'analise_run_id',
        'timestamp_utc',
        'message_id',
        'sender',
        'recipient',
        'sender_ip',
        'sender_port',
        'type',
    ];

    protected $casts = [
        'timestamp_utc' => 'datetime',
        'sender_port' => 'integer',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(AnaliseRun::class, 'analise_run_id');
    }
}
