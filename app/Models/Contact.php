<?php
// app/Models/Contact.php

namespace App\Models;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    protected $fillable = [
        'phone',
        'name',
        'group_tag',
        'is_group',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'is_group' => 'boolean',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Get or create contact by phone number
     */
    public static function findOrCreateByPhone(string $phone): self
    {
        return static::firstOrCreate(
            ['phone' => $phone],
            ['last_message_at' => now()]
        );
    }

    /**
     * Update last message timestamp
     */
    public function updateLastMessageTime(): void
    {
        $this->update(['last_message_at' => now()]);
    }

    /**
     * Get next message timestamp for chronological ordering.
     * Ensures outgoing messages are always after the last known message.
     */
    public function nextMessageTimestamp(): Carbon
    {
        $lastMessage = $this->messages()->orderByDesc('message_timestamp')->first();

        // If there's a last message, return its timestamp + 1 second
        // to ensure new outgoing message is always after it.
        // Otherwise, just return current time.
        if ($lastMessage) {
            return $lastMessage->message_timestamp->copy()->addSecond();
        }

        return Carbon::now();
    }
}
