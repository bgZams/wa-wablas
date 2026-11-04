<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\WahaService;
use App\Services\MessageSyncService;
use Illuminate\Support\Facades\Log;

Schedule::command('schedule:dispatch')->everyMinute()->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

 
// Verify conversation for specific phone
Artisan::command('verify {phone}', function ($phone) {
    $this->call('verify:conversation', ['phone' => $phone]);
})->purpose('Quick verify conversation data');

// Quick sync with correct limit
Artisan::command('sync:customer {phone}', function ($phone) {
    $this->info("🔄 Syncing messages for customer: {$phone}");
    $this->newLine();
    
    // Wablas max limit is 500
    $this->call('messages:sync', [
        '--phone' => $phone,
        '--limit' => 500
    ]);
    
    $this->newLine();
    $this->info("📊 Verifying data...");
    $this->call('verify:conversation', ['phone' => $phone]);
    
})->purpose('Sync and verify specific customer');

// Fix all contacts last_message_at
Artisan::command('fix:timestamps', function () {
    $this->info('🔧 Fixing contact timestamps...');
    
    $contacts = \App\Models\Contact::all();
    $fixed = 0;
    
    foreach ($contacts as $contact) {
        $lastMessage = $contact->messages()
            ->orderByDesc('message_timestamp')
            ->first();
        
        if ($lastMessage) {
            $contact->update([
                'last_message_at' => $lastMessage->message_timestamp
            ]);
            $fixed++;
        }
    }
    
    $this->info("✅ Fixed {$fixed} contacts");
})->purpose('Fix contact last_message_at timestamps');

// Show conversation in terminal (pretty format)
Artisan::command('show:chat {phone}', function ($phone) {
    $contact = \App\Models\Contact::where('phone', $phone)->first();
    
    if (!$contact) {
        $this->error("Contact not found: {$phone}");
        return 1;
    }
    
    $messages = \App\Models\Message::where('contact_id', $contact->id)
        ->orderBy('message_timestamp', 'asc')
        ->get();
    
    $this->info("💬 Chat with {$phone}");
    $this->line(str_repeat("=", 80));
    
    foreach ($messages as $msg) {
        $time = $msg->message_timestamp->format('H:i');
        $icon = $msg->type === 'outgoing' ? '🤖' : '👤';
        $sender = $msg->type === 'outgoing' ? 'BOT' : 'USER';
        
        $this->newLine();
        $this->line("[{$time}] {$icon} {$sender}:");
        $this->line($msg->message);
    }
    
    $this->newLine();
    $this->line(str_repeat("=", 80));
    
})->purpose('Show chat conversation in terminal');

// Quick test webhook for specific customer
Artisan::command('test:webhook {phone}', function ($phone) {
    $this->info("🧪 Testing webhook for: {$phone}");
    
    $testPayload = [
        'id' => 'test_' . time(),
        'phone' => $phone,
        'message' => 'Test webhook from artisan command',
        'status' => 'received',
        'type' => 'incoming',
        'timestamp' => time(),
    ];
    
    $this->info("Payload: " . json_encode($testPayload, JSON_PRETTY_PRINT));
    
    $response = \Illuminate\Support\Facades\Http::post(
        url('/webhook/waha'),
        $testPayload
    );
    
    $this->newLine();
    $this->info("Response: " . $response->body());
    $this->info("Status: " . $response->status());
    
})->purpose('Test webhook endpoint');