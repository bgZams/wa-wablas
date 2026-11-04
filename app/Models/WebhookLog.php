<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    protected $fillable = ['event', 'phone', 'status', 'payload'];

    protected $casts = ['payload' => 'array'];
}
