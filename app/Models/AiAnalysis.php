<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAnalysis extends Model
{
    protected $fillable = [
        'analise_run_id',
        'user_id',
        'tipo',
        'modelo',
        'pergunta',
        'contexto',
        'resposta',
    ];

    protected $casts = [
        'contexto' => 'array',
    ];

    public function analiseRun(): BelongsTo
    {
        return $this->belongsTo(AnaliseRun::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
