<?php
// app/Console/Commands/VerifyConversationCommand.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Contact;
use App\Models\Message;
use Carbon\Carbon;

class VerifyConversationCommand extends Command
{
    protected $signature = 'verify:conversation {phone}';
    protected $description = 'Verify conversation data matches between database and UI';

    public function handle()
    {
        $phone = $this->argument('phone');
        
        $this->info("🔍 Verifying conversation for: {$phone}");
        $this->newLine();

        // Find contact
        $contact = Contact::where('phone', $phone)->first();

        if (!$contact) {
            $this->error("❌ Contact not found: {$phone}");
            return 1;
        }

        $this->info("✅ Contact found: ID {$contact->id}");
        $this->info("   Name: " . ($contact->name ?: 'N/A'));
        $this->info("   Last message: " . ($contact->last_message_at ? $contact->last_message_at->format('Y-m-d H:i:s') : 'N/A'));
        $this->newLine();

        // Get messages (same query as controller)
        $messages = Message::where('contact_id', $contact->id)
            ->orderBy('message_timestamp', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($messages->isEmpty()) {
            $this->warn("⚠️  No messages found for this contact");
            return 0;
        }

        $this->info("📊 Total messages: " . $messages->count());
        $this->info("   - Incoming: " . $messages->where('type', 'incoming')->count());
        $this->info("   - Outgoing: " . $messages->where('type', 'outgoing')->count());
        $this->newLine();

        // Display conversation
        $this->info("💬 Conversation Timeline:");
        $this->line(str_repeat("=", 80));

        $currentDate = null;
        
        foreach ($messages as $message) {
            $messageDate = $message->message_timestamp->format('Y-m-d');
            
            // Show date divider
            if ($currentDate !== $messageDate) {
                $this->newLine();
                $this->line("📅 " . $message->message_timestamp->format('d M Y'));
                $this->line(str_repeat("-", 80));
                $currentDate = $messageDate;
            }

            // Message display
            $time = $message->message_timestamp->format('H:i:s');
            $type = $message->type === 'outgoing' ? '📤 BOT' : '📥 USER';
            $status = $message->status;
            $preview = strlen($message->message) > 60 
                ? substr($message->message, 0, 60) . '...' 
                : $message->message;
            
            $this->line(sprintf(
                "[%s] %s (%s) | ID:%d",
                $time,
                $type,
                $status,
                $message->id
            ));
            $this->line("  " . $preview);
            $this->newLine();
        }

        $this->line(str_repeat("=", 80));
        $this->newLine();

        // URLs to check
        $this->info("🌐 Check in browser:");
        $baseUrl = config('app.url', 'http://localhost:8000');
        $this->line("   Dashboard: {$baseUrl}/dashboard");
        $this->line("   This conversation: {$baseUrl}/conversation/{$phone}");
        $this->newLine();

        // Data structure that will be passed to view
        $this->info("📋 Data structure for view:");
        $conversationLogs = $messages->map(function ($message) {
            return [
                'phone' => $message->contact->phone,
                'message' => $message->message,
                'status' => $message->status,
                'type' => $message->type,
                'timestamp' => $message->message_timestamp->timestamp,
                'formatted_date' => $message->message_timestamp->format('d M Y'),
                'formatted_time' => $message->message_timestamp->format('H:i'),
            ];
        });

        $this->table(
            ['Type', 'Time', 'Message Preview', 'Status'],
            $conversationLogs->map(function($log) {
                return [
                    $log['type'],
                    $log['formatted_date'] . ' ' . $log['formatted_time'],
                    substr($log['message'], 0, 40),
                    $log['status']
                ];
            })
        );

        $this->newLine();
        $this->info("✅ Verification complete!");
        
        return 0;
    }
}