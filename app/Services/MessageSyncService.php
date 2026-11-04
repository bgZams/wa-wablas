<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Message;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MessageSyncService
{
    protected string $botNumber;
    protected WahaService $waha;

    public function __construct(WahaService $waha)
    {
        $this->waha = $waha;
        $this->botNumber = config('waha.bot_number', '6282284571236');
    }

    /**
     * Sync messages from WAHA API (chats overview or chat messages array)
     */
    public function syncMessagesFromApi(array $apiMessages): array
    {
        $synced = 0;
        $skipped = 0;
        $updated = 0;

        DB::beginTransaction();

        try {
            foreach ($apiMessages as $item) {
                $msgObj = $item;
                $isOverview = false;

                if (isset($item['lastMessage']) && is_array($item['lastMessage'])) {
                    $isOverview = true;
                    $msgObj = $item['lastMessage'];
                }

                if (empty($msgObj) || !is_array($msgObj)) {
                    $skipped++;
                    continue;
                }

                $rawId = $msgObj['id'] ?? null;
                $messageId = is_array($rawId) ? ($rawId['_serialized'] ?? $rawId['id'] ?? null) : $rawId;
                $text = $msgObj['body'] ?? $msgObj['text'] ?? $msgObj['message'] ?? $msgObj['caption'] ?? '';
                $timestampRaw = $msgObj['timestamp'] ?? $msgObj['date'] ?? null;
                $from = $msgObj['from'] ?? '';
                $to = $msgObj['to'] ?? '';
                $fromMe = (bool) ($msgObj['fromMe'] ?? false);
                $hasMedia = (bool) ($msgObj['hasMedia'] ?? false);

                // Resolve LID to real phone number
                $fromResolved = $this->waha->resolveToPhone($from);
                $toResolved = $this->waha->resolveToPhone($to);

                // Identify customer phone & direction
                if ($fromMe || $fromResolved === $this->botNumber) {
                    $customerPhone = $toResolved ?: ($isOverview ? $this->waha->resolveToPhone($item['id'] ?? '') : null);
                    $type = 'outgoing';
                } else {
                    $customerPhone = $fromResolved ?: ($isOverview ? $this->waha->resolveToPhone($item['id'] ?? '') : null);
                    $type = 'incoming';
                }

                if (empty($customerPhone) || empty($timestampRaw)) {
                    $skipped++;
                    continue;
                }

                // If message body is empty but hasMedia is true, give a descriptive label
                $kind = 'text';
                $metadata = null;

                if ($hasMedia || !empty($msgObj['media'])) {
                    $kind = 'image'; // default media kind
                    $chatIdentifier = $msgObj['from'] ?? ($item['id'] ?? $customerPhone);

                    // Check if we already have the media downloaded or need to download
                    $downloaded = $this->waha->downloadMessageMedia($chatIdentifier, $messageId);
                    if ($downloaded) {
                        $metadata = $downloaded;
                        $mime = $downloaded['mimetype'] ?? '';
                        if (str_starts_with($mime, 'image/')) $kind = 'image';
                        elseif (str_starts_with($mime, 'audio/')) $kind = 'audio';
                        elseif (str_starts_with($mime, 'video/')) $kind = 'video';
                        else $kind = 'document';
                    }

                    if (empty($text)) {
                        $text = match ($kind) {
                            'image' => '📷 [Foto / Gambar]',
                            'audio' => '🎵 [Pesan Suara]',
                            'video' => '🎥 [Video]',
                            'document' => '📄 [Dokumen]',
                            default => '📎 [Media]',
                        };
                    }
                } elseif (isset($msgObj['location']) && !empty($msgObj['location'])) {
                    $kind = 'location';
                    $metadata = $msgObj['location'];
                    if (empty($text)) {
                        $text = "📍 Lokasi: {$metadata['latitude']}, {$metadata['longitude']}";
                    }
                }

                if (empty($text)) {
                    $text = 'Pesan';
                }

                $msgTimestamp = is_numeric($timestampRaw)
                    ? Carbon::createFromTimestamp($timestampRaw)
                    : Carbon::parse($timestampRaw);

                $isGroup = str_contains($from, '@g.us') || str_contains($to, '@g.us') || ($isOverview && str_contains($item['id'] ?? '', '@g.us'));

                $contact = Contact::where('phone', $customerPhone)->first();
                if (!$contact) {
                    $contactName = $item['name'] ?? null;
                    $contact = Contact::create([
                        'phone' => $customerPhone,
                        'name' => $contactName,
                        'is_group' => $isGroup,
                        'last_message_at' => $msgTimestamp,
                    ]);
                } else {
                    if ($isGroup && !$contact->is_group) {
                        $contact->update(['is_group' => true]);
                    }
                    if (!empty($item['name']) && empty($contact->name)) {
                        $contact->update(['name' => $item['name']]);
                    }
                }

                $status = 'sent';
                if ($type === 'incoming') {
                    $status = 'received';
                } elseif (isset($msgObj['ack'])) {
                    $status = match ((int)$msgObj['ack']) {
                        -1 => 'failed',
                        0 => 'pending',
                        default => 'sent',
                    };
                }

                $uniqueId = $messageId ?: ('sync_' . md5($customerPhone . $text . $msgTimestamp->timestamp));

                $existing = Message::where('wablas_message_id', $uniqueId)->first();
                if ($existing) {
                    $toUpdate = [];
                    if ($existing->status !== $status) {
                        $toUpdate['status'] = $status;
                    }
                    if ($metadata && empty($existing->metadata)) {
                        $toUpdate['metadata'] = $metadata;
                        $toUpdate['kind'] = $kind;
                        if ($existing->message === 'Pesan' || empty($existing->message)) {
                            $toUpdate['message'] = $text;
                        }
                    }
                    if (!empty($toUpdate)) {
                        $existing->update($toUpdate);
                        $updated++;
                    } else {
                        $skipped++;
                    }
                } else {
                    Message::create([
                        'contact_id' => $contact->id,
                        'wablas_message_id' => $uniqueId,
                        'message' => $text,
                        'kind' => $kind,
                        'metadata' => $metadata,
                        'type' => $type,
                        'status' => $status,
                        'message_timestamp' => $msgTimestamp,
                    ]);
                    $synced++;
                }

                if (!$contact->last_message_at || $msgTimestamp->gt($contact->last_message_at)) {
                    $contact->update(['last_message_at' => $msgTimestamp]);
                }
            }

            DB::commit();

            return [
                'success' => true,
                'synced' => $synced,
                'updated' => $updated,
                'skipped' => $skipped,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('MessageSyncService error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'synced' => $synced,
                'updated' => $updated,
                'skipped' => $skipped,
            ];
        }
    }
}
