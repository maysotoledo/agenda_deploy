<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnaliseInvestigation extends Model
{
    protected $fillable = [
        'user_id',
        'uuid',
        'name',
        'source',
    ];

    public function runs(): HasMany
    {
        return $this->hasMany(AnaliseRun::class, 'investigation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
