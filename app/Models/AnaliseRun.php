<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\Auditable;

class AnaliseRun extends Model
{
    use Auditable;
    protected $fillable = [
        'user_id', // ✅ novo
        'investigation_id',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function investigation(): BelongsTo
    {
        return $this->belongsTo(AnaliseInvestigation::class, 'investigation_id');
    }
}
