<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledMessage extends Model
{
    protected $fillable = [
        'phone', 'is_group', 'kind', 'message',
        'latitude', 'longitude', 'link_url',
        'scheduled_at', 'status', 'response',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'is_group' => 'boolean',
    ];
}
