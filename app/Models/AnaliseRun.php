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
        'source',
        'target',
        'total_unique_ips',
        'processed_unique_ips',
        'progress',
        'status',
        'error_message',
        'report',
        'summary',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'report' => 'array',
        'summary' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function aiAnalyses(): HasMany
    {
        return $this->hasMany(AiAnalysis::class);
    }

    public function ips(): HasMany
    {
        return $this->hasMany(AnaliseRunIp::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(AnaliseRunEvent::class, 'analise_run_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(AnaliseRunStep::class, 'analise_run_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(AnaliseRunContact::class, 'analise_run_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AnaliseRunMessage::class, 'analise_run_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(AnaliseRunMedia::class, 'analise_run_id');
    }

    public function investigation(): BelongsTo
    {
        return $this->belongsTo(AnaliseInvestigation::class, 'investigation_id');
    }
}
