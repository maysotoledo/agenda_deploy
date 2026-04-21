<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnaliseRunContact extends Model
{
    protected $fillable = [
        'analise_run_id',
        'phone',
        'name',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(AnaliseRun::class, 'analise_run_id');
    }
}
