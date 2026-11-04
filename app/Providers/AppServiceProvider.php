<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\MessageSyncService;
use App\Models\Message;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MessageSyncService::class);
    }

    public function boot(): void
    {
        
        // View::composer('*') artinya data ini tersedia di SEMUA view.
        // Anda bisa mengganti '*' dengan 'layouts.sidebar' atau 'dashboard' agar lebih spesifik.
        View::composer('*', function ($view) {
            
            // Gunakan Cache selama 10 menit agar tidak terus-menerus query ke database
            $stats = Cache::remember('message_stats', 600, function () {
                return [
                    'totalSent'     => Message::where('type', 'outgoing')->where('status', 'sent')->count(),
                    'totalReceived' => Message::where('type', 'incoming')->count(),
                    'totalFailed'   => Message::where('type', 'outgoing')->where('status', 'failed')->count(),
                ];
            });

            $view->with($stats);
        });
    }
}