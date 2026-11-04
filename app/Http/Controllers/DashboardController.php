<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WahaService;
use App\Services\MessageSyncService;
use App\Models\Contact;
use App\Models\Message;
use App\Models\ScheduledMessage;
use App\Models\AutoReply;
use App\Models\Setting;
use App\Models\WebhookLog;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class DashboardController extends Controller
{
    protected $wahaService;
    protected $messageSyncService;

    public function __construct(WahaService $wahaService, MessageSyncService $messageSyncService)
    {
        $this->wahaService = $wahaService;
        $this->messageSyncService = $messageSyncService;
    }

    public function index()
    {
        // $this->pruneOldMessages(); // Disabled to preserve chat history

        // Fast load without blocking API call

        $stats = $this->buildStats();
        $contacts = $this->buildContactList();
        $autoReplies = AutoReply::orderByDesc('id')->get();
        $scheduled = ScheduledMessage::orderBy('scheduled_at')->get();
        $webhookUrl = url('/webhook/waha');
        $settings = [
            'auto_reply_enabled' => Setting::get('auto_reply_enabled', '1'),
            'webhook_tracking' => Setting::get('webhook_tracking', '1'),
        ];

        return view('dashboard.index', compact(
            'stats', 'contacts', 'autoReplies', 'scheduled', 'webhookUrl', 'settings'
        ));
    }

    public function conversation(string $phone)
    {
        // Instant load conversation from local database

        $contact = Contact::where('phone', $phone)->first();
        if (!$contact) {
            return response()->json(['messages' => [], 'contact' => null]);
        }

        $messages = Message::where('contact_id', $contact->id)
            ->orderByRaw("message_timestamp ASC, CASE WHEN type='incoming' THEN 0 ELSE 1 END ASC, created_at ASC, id ASC")
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'message' => $m->message,
                'kind' => $m->kind ?? 'text',
                'metadata' => $m->metadata,
                'type' => $m->type,
                'status' => $m->status,
                'timestamp' => $m->message_timestamp->timestamp,
                'time' => $m->message_timestamp->format('H:i'),
                'date' => $m->message_timestamp->format('Y-m-d'),
                'date_label' => $m->message_timestamp->isToday() ? 'Hari Ini'
                    : ($m->message_timestamp->isYesterday() ? 'Kemarin'
                    : $m->message_timestamp->format('d M Y')),
            ]);

        return response()->json([
            'contact' => [
                'phone' => $contact->phone,
                'name' => $contact->name ?? $contact->phone,
                'is_group' => (bool) $contact->is_group,
            ],
            'messages' => $messages,
        ]);
    }

    public function reply(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string|max:50',
            'message' => 'required|string',
            'is_group' => 'nullable|boolean',
        ]);

        $response = $this->wahaService->sendMessage(
            $data['phone'], $data['message'], (bool) ($data['is_group'] ?? false)
        );

        $ok = isset($response['status']) && $response['status'] === true;
        if ($ok) {
            $contact = Contact::findOrCreateByPhone($data['phone']);
            if (!empty($data['is_group'])) {
                $contact->update(['is_group' => true]);
            }
            $ts = $contact->nextMessageTimestamp();
            Message::updateOrCreate(
                ['wablas_message_id' => $response['data']['id'] ?? uniqid('reply_')],
                [
                    'contact_id' => $contact->id,
                    'message' => $data['message'],
                    'kind' => 'text',
                    'type' => 'outgoing',
                    'status' => 'sent',
                    'message_timestamp' => $ts,
                ]
            );
            $contact->update(['last_message_at' => $ts]);
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => $ok, 'response' => $response]);
        }
        return redirect()->route('conversation', ['phone' => $data['phone']])
            ->with($ok ? 'success' : 'error', $ok ? 'Terkirim' : 'Gagal kirim');
    }

    public function sendLocation(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'caption' => 'nullable|string',
        ]);

        $response = $this->wahaService->sendLocation(
            $data['phone'], $data['latitude'], $data['longitude'], $data['caption'] ?? ''
        );

        $ok = isset($response['status']) && $response['status'] === true;
        if ($ok) {
            $contact = Contact::findOrCreateByPhone($data['phone']);
            $ts = $contact->nextMessageTimestamp();
            Message::create([
                'contact_id' => $contact->id,
                'wablas_message_id' => uniqid('loc_'),
                'message' => "📍 Lokasi: {$data['latitude']}, {$data['longitude']}" . ($data['caption'] ? "\n{$data['caption']}" : ''),
                'kind' => 'location',
                'metadata' => ['lat' => $data['latitude'], 'lng' => $data['longitude']],
                'type' => 'outgoing',
                'status' => 'sent',
                'message_timestamp' => $ts,
            ]);
            $contact->update(['last_message_at' => $ts]);
        }

        return response()->json(['success' => $ok, 'response' => $response]);
    }

    public function sendLink(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string',
            'url' => 'required|url',
            'message' => 'nullable|string',
        ]);

        $response = $this->wahaService->sendLink($data['phone'], $data['url'], $data['message'] ?? '');
        $ok = isset($response['status']) && $response['status'] === true;

        if ($ok) {
            $contact = Contact::findOrCreateByPhone($data['phone']);
            $ts = $contact->nextMessageTimestamp();
            Message::create([
                'contact_id' => $contact->id,
                'wablas_message_id' => uniqid('link_'),
                'message' => trim(($data['message'] ?? '') . "\n" . $data['url']),
                'kind' => 'link',
                'metadata' => ['url' => $data['url']],
                'type' => 'outgoing',
                'status' => 'sent',
                'message_timestamp' => $ts,
            ]);
            $contact->update(['last_message_at' => $ts]);
        }

        return response()->json(['success' => $ok, 'response' => $response]);
    }

    public function checkNumber(Request $request)
    {
        $data = $request->validate(['phone' => 'required|string']);
        $response = $this->wahaService->checkWhatsappStatus($data['phone']);
        return response()->json($response);
    }

    public function schedule(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string',
            'is_group' => 'nullable|boolean',
            'kind' => 'required|in:text,location,link',
            'message' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'link_url' => 'nullable|url',
            'scheduled_at' => 'required|date|after:now',
        ]);

        $sched = ScheduledMessage::create(array_merge($data, [
            'status' => 'pending',
            'is_group' => (bool) ($data['is_group'] ?? false),
        ]));

        return response()->json(['success' => true, 'scheduled' => $sched]);
    }

    public function cancelSchedule($id)
    {
        $sched = ScheduledMessage::findOrFail($id);
        if ($sched->status === 'pending') {
            $sched->update(['status' => 'cancelled']);
        }
        return response()->json(['success' => true]);
    }

    public function listSchedules()
    {
        return response()->json(ScheduledMessage::orderBy('scheduled_at')->get());
    }

    public function saveAutoReply(Request $request)
    {
        $data = $request->validate([
            'id' => 'nullable|integer',
            'keyword' => 'required|string',
            'reply' => 'required|string',
            'match_type' => 'required|in:exact,contains,starts_with',
            'active' => 'nullable|boolean',
        ]);

        $payload = [
            'keyword' => $data['keyword'],
            'reply' => $data['reply'],
            'match_type' => $data['match_type'],
            'active' => (bool) ($data['active'] ?? true),
        ];

        $reply = !empty($data['id'])
            ? tap(AutoReply::findOrFail($data['id']))->update($payload)
            : AutoReply::create($payload);

        return response()->json(['success' => true, 'reply' => $reply]);
    }

    public function deleteAutoReply($id)
    {
        AutoReply::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    public function saveSettings(Request $request)
    {
        foreach ($request->only(['auto_reply_enabled', 'webhook_tracking']) as $k => $v) {
            Setting::put($k, $v ? '1' : '0');
        }
        return response()->json(['success' => true]);
    }

    public function importContacts(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
            'group_tag' => 'nullable|string',
        ]);

        $path = $request->file('file')->getRealPath();
        $handle = fopen($path, 'r');
        $imported = 0;
        $skipped = 0;
        $tag = $request->input('group_tag');

        while (($row = fgetcsv($handle)) !== false) {
            $phone = preg_replace('/[^0-9]/', '', $row[0] ?? '');
            if (strlen($phone) < 8) { $skipped++; continue; }
            if (str_starts_with($phone, '0')) $phone = '62' . substr($phone, 1);
            if (!str_starts_with($phone, '62')) $phone = '62' . $phone;
            $name = $row[1] ?? null;

            $contact = Contact::firstOrCreate(['phone' => $phone]);
            $contact->fill(array_filter([
                'name' => $name ?: $contact->name,
                'group_tag' => $tag ?: $contact->group_tag,
            ]))->save();
            $imported++;
        }
        fclose($handle);

        return response()->json(['success' => true, 'imported' => $imported, 'skipped' => $skipped]);
    }

    public function sendByGroup(Request $request)
    {
        $data = $request->validate([
            'group_tag' => 'required|string',
            'message' => 'required|string',
        ]);

        $contacts = Contact::where('group_tag', $data['group_tag'])->get();
        $sent = 0; $failed = 0;

        foreach ($contacts as $c) {
            $resp = $this->wahaService->sendMessage($c->phone, $data['message']);
            if (isset($resp['status']) && $resp['status'] === true) {
                $sent++;
                $ts = $c->nextMessageTimestamp();
                Message::create([
                    'contact_id' => $c->id,
                    'wablas_message_id' => uniqid('bulk_'),
                    'message' => $data['message'],
                    'kind' => 'text',
                    'type' => 'outgoing',
                    'status' => 'sent',
                    'message_timestamp' => $ts,
                ]);
                $c->update(['last_message_at' => $ts]);
            } else {
                $failed++;
            }
        }

        return response()->json(['success' => true, 'sent' => $sent, 'failed' => $failed, 'total' => $contacts->count()]);
    }

    public function webhookLogs()
    {
        return response()->json(
            WebhookLog::orderByDesc('id')->limit(100)->get()
        );
    }

    public function syncMessages()
    {
        try {
            $report = $this->wahaService->getMessageReport(['perPage' => 500]);

            if (!isset($report['status']) || $report['status'] !== true) {
                return response()->json([
                    'success' => false,
                    'error' => $report['message'] ?? 'API WAHA tidak merespons dengan benar',
                    'raw' => $report,
                ]);
            }

            $apiMessages = $report['message'] ?? $report['data'] ?? [];
            if (!is_array($apiMessages)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Format respons API tidak dikenali',
                ]);
            }

            $result = $this->messageSyncService->syncMessagesFromApi($apiMessages);

            return response()->json([
                'success' => (bool) ($result['success'] ?? false),
                'synced' => $result['synced'] ?? 0,
                'updated' => $result['updated'] ?? 0,
                'skipped' => $result['skipped'] ?? 0,
                'error' => $result['error'] ?? null,
            ]);
        } catch (\Exception $e) {
            \Log::error('Sync failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function contacts()
    {
        return response()->json($this->buildContactList());
    }

    public function exportContactsMessages()
    {
        $contacts = Contact::with(['messages' => fn ($q) => $q->orderByDesc('message_timestamp')->limit(1)])
            ->orderByDesc('last_message_at')
            ->get();

        $fileName = 'laporan_kontak_wabot_' . date('Ymd_His') . '.pdf';

        $data = [
            'contacts' => $contacts,
            'reportDate' => now(),
        ];

        $pdf = Pdf::loadView('reports.contacts_messages', $data);
        $pdf->setPaper('A4', 'landscape');

        return $pdf->download($fileName);
    }

    protected function buildContactList()
    {
        return Contact::with(['messages' => fn ($q) => $q->orderByDesc('message_timestamp')->limit(1)])
            ->orderByDesc('last_message_at')
            ->get()
            ->map(function ($c) {
                $m = $c->messages->first();
                return [
                    'phone' => $c->phone,
                    'name' => $c->name ?: $c->phone,
                    'group_tag' => $c->group_tag,
                    'is_group' => (bool) $c->is_group,
                    'latest_message' => $m?->message ?? '',
                    'latest_type' => $m?->type,
                    'timestamp' => $m?->message_timestamp?->timestamp ?? $c->last_message_at?->timestamp,
                    'time_label' => $m?->message_timestamp
                        ? $this->timeLabel($m->message_timestamp)
                        : '',
                ];
            });
    }

    protected function timeLabel(Carbon $t): string
    {
        if ($t->isToday()) return $t->format('H:i');
        if ($t->isYesterday()) return 'Kemarin';
        if ($t->diffInDays(now()) < 7) return $t->isoFormat('ddd');
        return $t->format('d/m/Y');
    }

    protected function buildStats(): array
    {
        return [
            'total_sent' => Message::where('type', 'outgoing')->where('status', 'sent')->count(),
            'total_received' => Message::where('type', 'incoming')->count(),
            'total_failed' => Message::where('type', 'outgoing')->where('status', 'failed')->count(),
            'total_contacts' => Contact::count(),
            'total_scheduled' => ScheduledMessage::where('status', 'pending')->count(),
        ];
    }

    protected function pruneOldMessages(): void
    {
        // $cutoff = now()->subDays(5);
        // Message::where('message_timestamp', '<', $cutoff)->delete();
    }
}
