<?php
// app/Models/Message.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_id',
        'wablas_message_id',
        'message',
        'kind',
        'metadata',
        'type',
        'status',
        'message_timestamp',
    ];

    protected $casts = [
        'message_timestamp' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Relasi ke Contact
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Create message jika belum ada (cek duplikasi)
     * PERBAIKAN: Gunakan updateOrCreate agar lebih reliable
     */
    public static function createIfNotExists(array $data)
    {
        // Jika ada wablas_message_id, gunakan itu untuk cek duplikasi
        if (!empty($data['wablas_message_id'])) {
            return self::updateOrCreate(
                ['wablas_message_id' => $data['wablas_message_id']],
                $data
            );
        }

        // Jika tidak ada wablas_message_id, cek berdasarkan kombinasi field lain
        $existing = self::where('contact_id', $data['contact_id'])
            ->where('message', $data['message'])
            ->where('type', $data['type'])
            ->where('message_timestamp', $data['message_timestamp'])
            ->first();

        if ($existing) {
            // Update jika ada perubahan status
            $existing->update(['status' => $data['status']]);
            return null; // Return null karena bukan message baru
        }

        return self::create($data);
    }

    /**
     * Scope untuk filter berdasarkan tipe
     */
    public function scopeIncoming($query)
    {
        return $query->where('type', 'incoming');
    }

    public function scopeOutgoing($query)
    {
        return $query->where('type', 'outgoing');
    }

    /**
     * Scope untuk filter berdasarkan status
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Accessor untuk formatted timestamp
     */
    public function getFormattedTimeAttribute()
    {
        return Carbon::parse($this->message_timestamp)->format('d M Y H:i');
    }

    public function getTimeAgoAttribute()
    {
        return Carbon::parse($this->message_timestamp)->diffForHumans();
    }
}