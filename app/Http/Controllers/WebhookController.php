<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MessageSyncService;
use App\Services\WahaService;
use App\Models\Contact;
use App\Models\Message;
use App\Models\AutoReply;
use App\Models\Setting;
use App\Models\WebhookLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    protected MessageSyncService $messageSyncService;
    protected WahaService $wahaService;
    protected string $botNumber;

    public function __construct(MessageSyncService $messageSyncService, WahaService $wahaService)
    {
        $this->messageSyncService = $messageSyncService;
        $this->wahaService = $wahaService;
        $this->botNumber = config('waha.bot_number', '6282284571236');
    }

    /**
     * Handle incoming webhook from WAHA or Wablas
     */
    public function handleIncoming(Request $request)
    {
        try {
            $data = $request->all();

            Log::info('=== WEBHOOK RECEIVED ===', [
                'timestamp' => now()->toDateTimeString(),
                'payload' => $data,
            ]);

            if (empty($data)) {
                return response()->json(['message' => 'Empty payload'], 200);
            }

            // Check if this is a WAHA event wrapper
            $event = $data['event'] ?? 'message';
            $payload = isset($data['payload']) && is_array($data['payload']) ? $data['payload'] : $data;

            // Handle ACK event (message delivery/read status)
            if ($event === 'message.ack') {
                return $this->handleAck($payload, $data);
            }

            // Handle session status event
            if ($event === 'session.status') {
                Log::info('WAHA session status update', [
                    'session' => $data['session'] ?? 'unknown',
                    'status' => $payload['status'] ?? 'unknown',
                ]);
                return response()->json(['success' => true, 'event' => 'session_status_received']);
            }

            // Ignore duplicate message.any event (only process standard 'message' event)
            if ($event === 'message.any') {
                return response()->json(['success' => true, 'event' => 'ignored_message_any']);
            }

            // Extract sender and receiver
            $rawSender = $this->extractSender($payload);
            $rawReceiver = $this->extractReceiver($payload);
            $fromMe = (bool) ($payload['fromMe'] ?? false);

            // Resolve LID to regular phone number
            $senderDigits = $this->wahaService->resolveToPhone($rawSender ?? '');
            $receiverDigits = $this->wahaService->resolveToPhone($rawReceiver ?? '');

            $customerPhone = null;
            $messageType = 'incoming';

            if ($fromMe || $senderDigits === $this->botNumber) {
                $customerPhone = $receiverDigits;
                $messageType = 'outgoing';
            } elseif ($receiverDigits === $this->botNumber) {
                $customerPhone = $senderDigits;
                $messageType = 'incoming';
            } else {
                $customerPhone = $senderDigits ?: $receiverDigits;
            }

            if (!$customerPhone) {
                Log::warning('Webhook: No valid customer phone found', ['payload' => $payload]);
                return response()->json(['message' => 'No valid phone number'], 200);
            }

            $messageText = $this->extractMessage($payload);
            $hasMedia = (bool) ($payload['hasMedia'] ?? false) || !empty($payload['media']);
            $uniqueMessageId = $this->extractMessageId($payload);

            $kind = 'text';
            $metadata = null;

            // Handle Media (Image, Audio, Video, Document)
            if ($hasMedia) {
                $kind = 'image';
                $chatIdentifier = $payload['from'] ?? $customerPhone;
                $downloaded = $this->wahaService->downloadMessageMedia($chatIdentifier, $uniqueMessageId);

                if ($downloaded) {
                    $metadata = $downloaded;
                    $mime = $downloaded['mimetype'] ?? '';
                    if (str_starts_with($mime, 'image/')) $kind = 'image';
                    elseif (str_starts_with($mime, 'audio/')) $kind = 'audio';
                    elseif (str_starts_with($mime, 'video/')) $kind = 'video';
                    else $kind = 'document';
                }

                if (empty($messageText)) {
                    $messageText = match ($kind) {
                        'image' => '📷 [Foto / Gambar]',
                        'audio' => '🎵 [Pesan Suara]',
                        'video' => '🎥 [Video]',
                        'document' => '📄 [Dokumen]',
                        default => '📎 [Media]',
                    };
                }
            } elseif (isset($payload['location']) && !empty($payload['location'])) {
                $kind = 'location';
                $metadata = $payload['location'];
                if (empty($messageText)) {
                    $messageText = "📍 Lokasi: {$metadata['latitude']}, {$metadata['longitude']}";
                }
            }

            if (empty($messageText)) {
                $messageText = 'Pesan';
            }

            $status = $this->normalizeStatus($payload['status'] ?? ($messageType === 'incoming' ? 'received' : 'sent'));
            $timestamp = $this->extractTimestamp($payload);

            // Maintain correct order for instant bot replies
            if ($messageType === 'outgoing') {
                $contact = Contact::where('phone', $customerPhone)->first();
                if ($contact) {
                    $lastIncoming = Message::where('contact_id', $contact->id)
                        ->where('type', 'incoming')
                        ->whereBetween('message_timestamp', [
                            $timestamp->copy()->subSeconds(2),
                            $timestamp->copy()->addSeconds(2),
                        ])
                        ->latest('message_timestamp')
                        ->latest('id')
                        ->first();

                    if ($lastIncoming && $lastIncoming->message_timestamp->timestamp >= $timestamp->timestamp) {
                        $timestamp = $lastIncoming->message_timestamp->copy()->addSecond();
                    }
                }
            }

            $isGroup = str_contains($payload['from'] ?? '', '@g.us') || str_contains($payload['to'] ?? '', '@g.us');

            $contact = Contact::findOrCreateByPhone($customerPhone);
            if ($isGroup && !$contact->is_group) {
                $contact->update(['is_group' => true]);
            }

            // Update contact name if provided
            $pushName = $payload['_data']['notifyName'] ?? $payload['notifyName'] ?? null;
            if (!empty($pushName) && empty($contact->name)) {
                $contact->update(['name' => $pushName]);
            }

            $message = Message::updateOrCreate(
                [
                    'wablas_message_id' => $uniqueMessageId,
                ],
                [
                    'contact_id' => $contact->id,
                    'message' => $messageText,
                    'kind' => $kind,
                    'metadata' => $metadata,
                    'type' => $messageType,
                    'status' => $status,
                    'message_timestamp' => $timestamp,
                ]
            );

            if (!$contact->last_message_at || $timestamp->gt($contact->last_message_at)) {
                $contact->update([
                    'last_message_at' => $timestamp,
                ]);
            }

            // Webhook tracking log
            if (Setting::get('webhook_tracking', '1') === '1') {
                WebhookLog::create([
                    'event' => $event,
                    'phone' => $customerPhone,
                    'status' => $status,
                    'payload' => $data,
                ]);
            }

            // Trigger auto-reply if enabled and incoming
            if ($messageType === 'incoming' && Setting::get('auto_reply_enabled', '1') === '1') {
                $this->maybeAutoReply($customerPhone, $messageText, $isGroup);
            }

            return response()->json([
                'success' => true,
                'message' => 'Message processed',
                'data' => [
                    'customer_phone' => $customerPhone,
                    'type' => $messageType,
                    'status' => $status,
                    'timestamp' => $timestamp->toDateTimeString(),
                    'message_id' => $message->id,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Webhook Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle ACK delivery events from WAHA
     */
    protected function handleAck(array $payload, array $raw): \Illuminate\Http\JsonResponse
    {
        $messageId = $this->extractMessageId($payload);
        $ack = $payload['ack'] ?? null;
        $ackName = $payload['ackName'] ?? '';

        $status = match ((int)$ack) {
            -1 => 'failed',
            0 => 'pending',
            default => 'sent',
        };

        if ($messageId) {
            Message::where('wablas_message_id', $messageId)->update(['status' => $status]);
        }

        if (Setting::get('webhook_tracking', '1') === '1') {
            WebhookLog::create([
                'event' => 'message.ack',
                'phone' => $this->wahaService->resolveToPhone($payload['to'] ?? $payload['from'] ?? ''),
                'status' => $status . " ($ackName)",
                'payload' => $raw,
            ]);
        }

        return response()->json(['success' => true, 'event' => 'ack_processed']);
    }

    /**
     * Auto-reply evaluator
     */
    protected function maybeAutoReply(string $phone, string $text, bool $isGroup = false): void
    {
        // Prevent duplicate auto-replies within 5 seconds for the same message
        $dedupKey = "auto_reply_sent_{$phone}_" . md5(trim(strtolower($text)));
        if (\Illuminate\Support\Facades\Cache::has($dedupKey)) {
            Log::info("AutoReply duplicate prevented for [{$phone}]: text='{$text}'");
            return;
        }

        $rule = AutoReply::where('active', true)->get()->first(fn ($r) => $r->matches($text));
        if (!$rule) return;

        // Mark as replied immediately to lock
        \Illuminate\Support\Facades\Cache::put($dedupKey, true, now()->addSeconds(8));

        Log::info("AutoReply match found for [{$phone}]: keyword='{$rule->keyword}', match_type='{$rule->match_type}'");

        try {
            $resp = $this->wahaService->sendMessage($phone, $rule->reply, $isGroup);
            if (isset($resp['status']) && $resp['status'] === true) {
                $contact = Contact::findOrCreateByPhone($phone);
                $ts = $contact->nextMessageTimestamp();

                Message::create([
                    'contact_id' => $contact->id,
                    'wablas_message_id' => $resp['data']['id'] ?? uniqid('ar_'),
                    'message' => $rule->reply,
                    'kind' => 'text',
                    'type' => 'outgoing',
                    'status' => 'sent',
                    'message_timestamp' => $ts,
                ]);
                $contact->update(['last_message_at' => $ts]);

                Log::info("AutoReply successfully sent to [{$phone}]");
            } else {
                Log::warning("AutoReply sendMessage failed for [{$phone}]: " . ($resp['message'] ?? 'Unknown error'));
            }
        } catch (\Throwable $e) {
            Log::warning('AutoReply failed: ' . $e->getMessage());
        }
    }

    /**
     * Extract sender phone/chatId
     */
    private function extractSender(array $data): ?string
    {
        $fields = ['from', 'sender', 'phone', 'phoneNumber', 'author'];
        foreach ($fields as $field) {
            if (!empty($data[$field])) {
                $val = $data[$field];
                return is_array($val) ? ($val['from'] ?? $val['sender'] ?? null) : (string)$val;
            }
        }
        return null;
    }

    /**
     * Extract receiver phone/chatId
     */
    private function extractReceiver(array $data): ?string
    {
        $fields = ['to', 'receiver', 'destination'];
        foreach ($fields as $field) {
            if (!empty($data[$field])) {
                $val = $data[$field];
                return is_array($val) ? ($val['to'] ?? $val['receiver'] ?? null) : (string)$val;
            }
        }
        return null;
    }

    /**
     * Extract message body/text
     */
    private function extractMessage(array $data): ?string
    {
        return $data['body'] ?? $data['text'] ?? $data['message'] ?? $data['caption'] ?? null;
    }

    /**
     * Extract unique message ID
     */
    private function extractMessageId(array $data): string
    {
        $id = $data['id'] ?? $data['message_id'] ?? $data['msg_id'] ?? null;
        if (is_array($id)) {
            $id = $id['_serialized'] ?? $id['id'] ?? null;
        }

        return $id ?: ('wh_' . uniqid() . '_' . time());
    }

    /**
     * Normalize status
     */
    private function normalizeStatus(?string $status): string
    {
        $status = strtolower(trim($status ?? ''));
        return match ($status) {
            'success', 'delivered', 'read', 'received', 'sent' => 'sent',
            'pending' => 'pending',
            'failed', 'unsent', 'error' => 'failed',
            default => 'sent',
        };
    }

    /**
     * Extract message timestamp
     */
    private function extractTimestamp(array $data): Carbon
    {
        $fields = ['timestamp', 'created_at', 'date', 'time', 'message_timestamp'];
        foreach ($fields as $field) {
            if (!empty($data[$field])) {
                $val = $data[$field];
                try {
                    if (is_numeric($val)) {
                        return Carbon::createFromTimestamp($val);
                    }
                    return Carbon::parse($val);
                } catch (\Throwable) {
                    continue;
                }
            }
        }

        return Carbon::now();
    }
}
