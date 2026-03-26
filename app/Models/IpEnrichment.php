<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IpEnrichment extends Model
{
    protected $fillable = [
        'ip',
        'city',
        'isp',
        'org',
        'mobile',
        'status',
        'message',
        'fetched_at',
    ];

    protected $casts = [
        'mobile' => 'boolean',
        'fetched_at' => 'datetime',
    ];
}
