<?php
// app/Console/Commands/DebugMessagesCommand.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Contact;
use App\Models\Message;
use Carbon\Carbon;

class DebugMessagesCommand extends Command
{
    protected $signature = 'debug:messages {--phone= : Filter by phone number}';
    protected $description = 'Debug messages in database';

    public function handle()
    {
        $phone = $this->option('phone');

        $this->info('=== DEBUG MESSAGES DATABASE ===');
        $this->newLine();

        // Total counts
        $totalContacts = Contact::count();
        $totalMessages = Message::count();
        $totalIncoming = Message::where('type', 'incoming')->count();
        $totalOutgoing = Message::where('type', 'outgoing')->count();

        $this->info("Total Contacts: {$totalContacts}");
        $this->info("Total Messages: {$totalMessages}");
        $this->info("  - Incoming: {$totalIncoming}");
        $this->info("  - Outgoing: {$totalOutgoing}");
        $this->newLine();

        // Show contacts
        $this->info('=== CONTACTS ===');
        $contacts = $phone 
            ? Contact::where('phone', $phone)->get()
            : Contact::orderByDesc('last_message_at')->limit(10)->get();

        foreach ($contacts as $contact) {
            $msgCount = $contact->messages()->count();
            $lastMsg = $contact->last_message_at 
                ? Carbon::parse($contact->last_message_at)->format('Y-m-d H:i:s')
                : 'N/A';
            
            $this->line("Phone: {$contact->phone} | Messages: {$msgCount} | Last: {$lastMsg}");
        }
        $this->newLine();

        // Show recent messages
        $this->info('=== RECENT MESSAGES ===');
        $messagesQuery = Message::with('contact');
        
        if ($phone) {
            $contact = Contact::where('phone', $phone)->first();
            if ($contact) {
                $messagesQuery->where('contact_id', $contact->id);
            }
        }

        $messages = $messagesQuery->orderByDesc('message_timestamp')->limit(20)->get();

        $this->table(
            ['ID', 'Phone', 'Type', 'Status', 'Message', 'Timestamp'],
            $messages->map(function ($msg) {
                return [
                    $msg->id,
                    $msg->contact->phone,
                    $msg->type,
                    $msg->status,
                    substr($msg->message, 0, 50),
                    Carbon::parse($msg->message_timestamp)->format('Y-m-d H:i:s')
                ];
            })
        );

        $this->newLine();

        // Check for sorting issues
        if ($phone) {
            $contact = Contact::where('phone', $phone)->first();
            if ($contact) {
                $this->info('=== SORTING CHECK ===');
                $sortedMessages = Message::where('contact_id', $contact->id)
                    ->orderBy('message_timestamp', 'asc')
                    ->get(['id', 'message_timestamp', 'type']);

                foreach ($sortedMessages as $msg) {
                    $time = Carbon::parse($msg->message_timestamp)->format('Y-m-d H:i:s');
                    $this->line("ID: {$msg->id} | Type: {$msg->type} | Time: {$time}");
                }
            }
        }

        return 0;
    }
}