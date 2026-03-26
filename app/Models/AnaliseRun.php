<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnaliseRun extends Model
{
    protected $fillable = [
        'uuid',
        'target',
        'total_unique_ips',
        'processed_unique_ips',
        'progress',
        'status',
        'error_message',
        'report',
    ];

    protected $casts = [
        'report' => 'array',
    ];

    public function ips(): HasMany
    {
        return $this->hasMany(AnaliseRunIp::class);
    }
}
