<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnaliseRunIp extends Model
{
    protected $fillable = [
        'analise_run_id',
        'ip',
        'last_seen_at',
        'occurrences',
        'enriched',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'enriched' => 'boolean',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(AnaliseRun::class, 'analise_run_id');
    }
}
