<?php

namespace App\Console\Commands;

use App\Models\ScheduledMessage;
use App\Models\Contact;
use App\Models\Message;
use App\Services\WahaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DispatchScheduledMessagesCommand extends Command
{
    protected $signature = 'schedule:dispatch';
    protected $description = 'Send all pending scheduled messages whose time has come';

    public function handle(WahaService $waha): int
    {
        $due = ScheduledMessage::where('status', 'pending')
            ->where('scheduled_at', '<=', now())
            ->get();

        $this->info("Found {$due->count()} due scheduled messages");

        foreach ($due as $sm) {
            try {
                $resp = match ($sm->kind) {
                    'location' => $waha->sendLocation($sm->phone, $sm->latitude, $sm->longitude, $sm->message ?? ''),
                    'link' => $waha->sendLink($sm->phone, $sm->link_url, $sm->message ?? ''),
                    default => $waha->sendMessage($sm->phone, $sm->message, $sm->is_group),
                };

                $ok = isset($resp['status']) && $resp['status'] === true;
                $sm->update([
                    'status' => $ok ? 'sent' : 'failed',
                    'response' => json_encode($resp),
                ]);

                if ($ok) {
                    $contact = Contact::findOrCreateByPhone($sm->phone);
                    Message::create([
                        'contact_id' => $contact->id,
                        'wablas_message_id' => uniqid('sched_'),
                        'message' => $sm->message ?? ($sm->link_url ?? "Lokasi {$sm->latitude},{$sm->longitude}"),
                        'kind' => $sm->kind,
                        'type' => 'outgoing',
                        'status' => 'sent',
                        'message_timestamp' => now(),
                    ]);
                    $contact->update(['last_message_at' => now()]);
                }

                $this->info("#{$sm->id} → " . ($ok ? 'sent' : 'failed'));
            } catch (\Exception $e) {
                $sm->update(['status' => 'failed', 'response' => $e->getMessage()]);
                Log::error('Schedule dispatch failed', ['id' => $sm->id, 'err' => $e->getMessage()]);
            }
            }

            // Message::where('message_timestamp', '<', now()->subDays(5))->delete(); // Disabled pruning to preserve history
        return self::SUCCESS;
    }
}
