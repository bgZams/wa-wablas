<?php
// app/Console/Commands/SyncMessagesCommand.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WahaService;
use App\Services\MessageSyncService;
use Carbon\Carbon;

class SyncMessagesCommand extends Command
{
    protected $signature = 'messages:sync 
                            {--phone= : Sync messages for specific phone number}
                            {--hours=1 : Number of hours to sync (default: 1)}
                            {--limit=500 : Maximum messages to fetch (default: 500)}';
    
    protected $description = 'Sync messages from WAHA API to database';

    protected $wahaService;
    protected $messageSyncService;

    public function __construct(WahaService $wahaService, MessageSyncService $messageSyncService)
    {
        parent::__construct();
        $this->wahaService = $wahaService;
        $this->messageSyncService = $messageSyncService;
    }

    public function handle()
    {
        $this->info('🔄 Starting message synchronization...');
        $this->newLine();

        $phone = $this->option('phone');
        $hours = $this->option('hours');
        $limit = $this->option('limit');

        // Prepare parameters - Wablas max limit is 500
        $maxLimit = min($limit, 500);
        
        $params = [
            'perPage' => $maxLimit,
        ];

        if ($phone) {
            $params['phone'] = $phone;
            $this->info("📱 Syncing for phone: {$phone}");
        } else {
            $this->info("📱 Syncing all messages from last {$hours} hour(s)");
        }
        
        $this->info("📊 Request limit: {$maxLimit} (Wablas max: 500)");
        $this->newLine();

        // Fetch messages from WAHA API
        $this->line('⏳ Fetching messages from WAHA API...');
        $report = $this->wahaService->getMessageReport($params);

        if (!isset($report['status']) || $report['status'] !== true) {
            $this->error('❌ Failed to fetch messages from WAHA API');
            $this->error('Response: ' . json_encode($report));
            return 1;
        }

        $messages = $report['message'] ?? [];
        $totalFetched = count($messages);

        if ($totalFetched === 0) {
            $this->warn('⚠️  No messages found');
            return 0;
        }

        $this->info("✅ Fetched {$totalFetched} messages from API");
        $this->newLine();

        // Filter messages by time if needed
        if (!$phone && $hours > 0) {
            $cutoffTime = Carbon::now()->subHours($hours);
            $messages = array_filter($messages, function($msg) use ($cutoffTime) {
                $timestamp = $msg['date']['created_at'] ?? $msg['created_at'] ?? null;
                if (!$timestamp) return false;
                
                try {
                    $msgTime = Carbon::parse($timestamp);
                    return $msgTime->gte($cutoffTime);
                } catch (\Exception $e) {
                    return false;
                }
            });
            
            $this->info("🕐 Filtered to " . count($messages) . " messages from last {$hours} hour(s)");
        }

        // Sync to database
        $this->line('💾 Syncing to database...');
        $bar = $this->output->createProgressBar(count($messages));
        $bar->start();

        $result = $this->messageSyncService->syncMessagesFromApi($messages);

        $bar->finish();
        $this->newLine(2);

        if ($result['success']) {
            $this->info('✅ Synchronization completed successfully!');
            $this->newLine();
            
            $this->table(
                ['Status', 'Count'],
                [
                    ['New messages', $result['synced']],
                    ['Updated messages', $result['updated'] ?? 0],
                    ['Skipped', $result['skipped']],
                    ['Total processed', count($messages)],
                ]
            );
        } else {
            $this->error('❌ Synchronization failed');
            $this->error('Error: ' . ($result['error'] ?? 'Unknown error'));
            return 1;
        }

        return 0;
    }
}