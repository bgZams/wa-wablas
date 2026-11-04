<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WahaService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $session;
    protected string $botNumber;

    // Cache static mapping LID -> Phone Number in-memory
    protected static array $lidCache = [];

    public function __construct()
    {
        $this->baseUrl = rtrim(config('waha.base_url', 'https://traveler-call-substantially-defeat.trycloudflare.com'), '/');
        $this->apiKey = config('waha.api_key', '');
        $this->session = config('waha.session', 'default');
        $this->botNumber = config('waha.bot_number', '6282284571236');
    }

    /**
     * Resolve LID or Phone to regular clean phone digits (e.g. 6281361682221)
     */
    public function resolveToPhone(string $id): string
    {
        $clean = trim($id);
        if (empty($clean)) return '';

        // If it's a group, return group digits/id
        if (str_contains($clean, '@g.us') || str_contains($clean, '-')) {
            return preg_replace('/@g\.us$/', '', $clean);
        }

        // If it's an LID (ends with @lid or is purely 14-15 random digits from LID)
        if (str_contains($clean, '@lid')) {
            $lidJid = $clean;
            if (isset(self::$lidCache[$lidJid])) {
                return self::$lidCache[$lidJid];
            }

            try {
                $response = $this->client()->get("{$this->baseUrl}/api/{$this->session}/lids/" . urlencode($lidJid));
                if ($response->successful()) {
                    $pn = $response->json('pn');
                    if ($pn) {
                        $resolvedDigits = $this->cleanDigits($pn);
                        self::$lidCache[$lidJid] = $resolvedDigits;
                        return $resolvedDigits;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("Failed to resolve LID {$lidJid}: " . $e->getMessage());
            }

            // Fallback: return digits of LID if resolution fails
            return $this->cleanDigits($clean);
        }

        return $this->cleanDigits($clean);
    }

    /**
     * Get or resolve LID for a phone number
     */
    public function resolveToLid(string $phone): ?string
    {
        $digits = $this->cleanDigits($phone);
        try {
            $response = $this->client()->get("{$this->baseUrl}/api/{$this->session}/lids/pn/{$digits}");
            if ($response->successful()) {
                return $response->json('lid');
            }
        } catch (\Throwable $e) {
            Log::warning("Failed to get LID for phone {$phone}: " . $e->getMessage());
        }
        return null;
    }

    /**
     * Format phone number to WhatsApp JID format (e.g. 628xxx@c.us or group@g.us)
     */
    public function formatChatId(string $phone, bool $isGroup = false): string
    {
        $clean = trim($phone);
        if (str_contains($clean, '@c.us') || str_contains($clean, '@g.us') || str_contains($clean, '@lid')) {
            return $clean;
        }

        if ($isGroup || str_contains($clean, '-')) {
            return $clean . '@g.us';
        }

        $digits = preg_replace('/[^0-9]/', '', $clean);
        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (!str_starts_with($digits, '62') && strlen($digits) >= 9) {
            $digits = '62' . $digits;
        }

        return $digits . '@c.us';
    }

    /**
     * Clean phone number digits
     */
    public function cleanDigits(string $id): string
    {
        $clean = preg_replace('/@(c|g)\.us$/', '', trim($id));
        $clean = preg_replace('/@lid$/', '', $clean);
        $digits = preg_replace('/[^0-9]/', '', $clean);
        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        }
        return $digits;
    }

    /**
     * Basic HTTP request helper with WAHA headers
     */
    protected function client()
    {
        $headers = [
            'Accept' => 'application/json',
        ];

        if (!empty($this->apiKey)) {
            $headers['X-Api-Key'] = $this->apiKey;
        }

        return Http::withHeaders($headers)->timeout(30);
    }

    /**
     * Send plain text message
     */
    public function sendMessage(string $phone, string $message, bool $isGroup = false): array
    {
        try {
            $chatId = $this->formatChatId($phone, $isGroup);

            $response = $this->client()->post("{$this->baseUrl}/api/sendText", [
                'session' => $this->session,
                'chatId' => $chatId,
                'text' => $message,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'status' => true,
                    'data' => [
                        'id' => $data['id'] ?? null,
                        'message' => $data,
                    ],
                    'message' => 'Message sent successfully',
                ];
            }

            Log::error('WAHA sendMessage failed', [
                'phone' => $phone,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'status' => false,
                'message' => $response->json('message') ?? $response->body() ?? 'Failed to send message',
            ];
        } catch (\Throwable $e) {
            Log::error('WAHA sendMessage exception: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send group message helper
     */
    public function sendGroupMessage(string $groupId, string $message): array
    {
        return $this->sendMessage($groupId, $message, true);
    }

    /**
     * Send Location pin
     */
    public function sendLocation(string $phone, $latitude, $longitude, string $caption = ''): array
    {
        try {
            $chatId = $this->formatChatId($phone);

            $payload = [
                'session' => $this->session,
                'chatId' => $chatId,
                'latitude' => (float) $latitude,
                'longitude' => (float) $longitude,
            ];

            if (!empty($caption)) {
                $payload['title'] = $caption;
            }

            $response = $this->client()->post("{$this->baseUrl}/api/sendLocation", $payload);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'status' => true,
                    'data' => [
                        'id' => $data['id'] ?? null,
                        'message' => $data,
                    ],
                    'message' => 'Location sent successfully',
                ];
            }

            return [
                'status' => false,
                'message' => $response->json('message') ?? $response->body() ?? 'Failed to send location',
            ];
        } catch (\Throwable $e) {
            Log::error('WAHA sendLocation exception: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send Link message with preview
     */
    public function sendLink(string $phone, string $url, string $message = ''): array
    {
        try {
            $chatId = $this->formatChatId($phone);
            $fullText = trim(($message ? $message . "\n" : '') . $url);

            $response = $this->client()->post("{$this->baseUrl}/api/sendLinkPreview", [
                'session' => $this->session,
                'chatId' => $chatId,
                'text' => $fullText,
                'url' => $url,
            ]);

            if (!$response->successful()) {
                return $this->sendMessage($phone, $fullText);
            }

            $data = $response->json();
            return [
                'status' => true,
                'data' => [
                    'id' => $data['id'] ?? null,
                    'message' => $data,
                ],
                'message' => 'Link sent successfully',
            ];
        } catch (\Throwable $e) {
            Log::error('WAHA sendLink exception: ' . $e->getMessage());
            return $this->sendMessage($phone, trim(($message ? $message . "\n" : '') . $url));
        }
    }

    /**
     * Check if WhatsApp number is active/exists
     */
    public function checkWhatsappStatus(string $phone): array
    {
        try {
            $digits = preg_replace('/[^0-9]/', '', $phone);
            if (str_starts_with($digits, '0')) {
                $digits = '62' . substr($digits, 1);
            }

            $response = $this->client()->get("{$this->baseUrl}/api/contacts/check-exists", [
                'session' => $this->session,
                'phone' => $digits,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $exists = (bool) ($data['numberExists'] ?? false);
                return [
                    'status' => true,
                    'exists' => $exists,
                    'chatId' => $data['chatId'] ?? null,
                    'message' => $exists ? 'Nomor terdaftar di WhatsApp' : 'Nomor tidak terdaftar di WhatsApp',
                ];
            }

            return [
                'status' => false,
                'message' => $response->json('message') ?? 'Gagal memeriksa status nomor',
            ];
        } catch (\Throwable $e) {
            Log::error('WAHA checkWhatsappStatus exception: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Download media from WAHA for a given message
     */
    public function downloadMessageMedia(string $chatId, string $messageId): ?array
    {
        try {
            // First fetch message details with downloadMedia=true
            $encodedChat = urlencode($chatId);
            $encodedMsg = urlencode($messageId);
            $url = "{$this->baseUrl}/api/{$this->session}/chats/{$encodedChat}/messages/{$encodedMsg}?downloadMedia=true";

            $res = $this->client()->get($url);
            if (!$res->successful()) {
                Log::warning("Failed to fetch message media info: " . $res->body());
                return null;
            }

            $msgData = $res->json();
            $mediaObj = $msgData['media'] ?? null;
            if (!$mediaObj || empty($mediaObj['url'])) {
                return null;
            }

            $wahaFileUrl = $mediaObj['url'];
            // If URL is localhost, replace with our baseUrl
            if (str_contains($wahaFileUrl, 'localhost:3000')) {
                $wahaFileUrl = str_replace('http://localhost:3000', $this->baseUrl, $wahaFileUrl);
            }

            $fileRes = $this->client()->get($wahaFileUrl);
            if (!$fileRes->successful()) {
                Log::warning("Failed to download media binary from {$wahaFileUrl}: " . $fileRes->status());
                return null;
            }

            $content = $fileRes->body();
            $mime = $mediaObj['mimetype'] ?? $fileRes->header('Content-Type') ?? 'application/octet-stream';
            $ext = match ($mime) {
                'image/jpeg', 'image/jpg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'audio/ogg', 'audio/ogg; codecs=opus' => 'ogg',
                'audio/mp3', 'audio/mpeg' => 'mp3',
                'video/mp4' => 'mp4',
                'application/pdf' => 'pdf',
                default => 'bin',
            };

            $fileName = 'wa_' . md5($messageId) . '.' . $ext;
            $storagePath = 'whatsapp/' . $fileName;

            Storage::disk('public')->put($storagePath, $content);
            $appUrl = rtrim(config('app.url', 'http://127.0.0.1:8000'), '/');
            $publicUrl = $appUrl . '/storage/' . $storagePath;

            return [
                'url' => $publicUrl,
                'path' => $storagePath,
                'mimetype' => $mime,
                'filename' => $mediaObj['filename'] ?? $fileName,
            ];
        } catch (\Throwable $e) {
            Log::error("downloadMessageMedia exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get chat messages or overview for sync
     */
    public function getChatMessages(string $chatId, int $limit = 50): array
    {
        try {
            $formattedChatId = $this->formatChatId($chatId);
            $response = $this->client()->get("{$this->baseUrl}/api/{$this->session}/chats/" . urlencode($formattedChatId) . "/messages", [
                'limit' => $limit,
                'downloadMedia' => false,
            ]);

            if ($response->successful()) {
                return [
                    'status' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'status' => false,
                'message' => $response->body(),
            ];
        } catch (\Throwable $e) {
            Log::error('WAHA getChatMessages exception: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get chats overview
     */
    public function getChatsOverview(int $limit = 100): array
    {
        try {
            $response = $this->client()->get("{$this->baseUrl}/api/{$this->session}/chats/overview", [
                'limit' => $limit,
            ]);

            if ($response->successful()) {
                return [
                    'status' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'status' => false,
                'message' => $response->body(),
            ];
        } catch (\Throwable $e) {
            Log::error('WAHA getChatsOverview exception: ' . $e->getMessage());
            return [
                'status' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Compatibility method matching previous Wablas getMessageReport
     */
    public function getMessageReport(array $params = []): array
    {
        try {
            $phone = $params['phone'] ?? null;
            if ($phone) {
                // If phone is an LID or has a mapped LID, try both
                $res = $this->getChatMessages($phone, $params['perPage'] ?? $params['limit'] ?? 100);
                if ($res['status']) {
                    return [
                        'status' => true,
                        'message' => $res['data'],
                    ];
                }

                // If regular phone didn't return messages, check if there's a mapped LID
                $lid = $this->resolveToLid($phone);
                if ($lid) {
                    $resLid = $this->getChatMessages($lid, $params['perPage'] ?? $params['limit'] ?? 100);
                    if ($resLid['status'] && !empty($resLid['data'])) {
                        return [
                            'status' => true,
                            'message' => $resLid['data'],
                        ];
                    }
                }

                return $res;
            }

            $overview = $this->getChatsOverview($params['perPage'] ?? 100);
            if ($overview['status']) {
                return [
                    'status' => true,
                    'message' => $overview['data'],
                ];
            }

            return $overview;
        } catch (\Throwable $e) {
            return [
                'status' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get active session info
     */
    public function getSessionInfo(): array
    {
        try {
            $response = $this->client()->get("{$this->baseUrl}/api/sessions/{$this->session}");
            return $response->json() ?? [];
        } catch (\Throwable $e) {
            return ['status' => 'FAILED', 'error' => $e->getMessage()];
        }
    }
}
