<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnaliseRunStep extends Model
{
    protected $fillable = [
        'analise_run_id',
        'step',
        'status',
        'total',
        'processed',
        'message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(AnaliseRun::class, 'analise_run_id');
    }
}
