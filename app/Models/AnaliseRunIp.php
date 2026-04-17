<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\Auditable;

class AnaliseRunIp extends Model
{
        use Auditable;
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
