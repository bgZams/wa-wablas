<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\WablasService;

class WebhookController extends Controller
{
    protected $wablasService;
    protected $logFilePath = 'message_logs.json';

    public function __construct(WablasService $wablasService)
    {
        $this->wablasService = $wablasService;
    }

    private function getLogs()
    {
        if (Storage::exists($this->logFilePath)) {
            return json_decode(Storage::get($this->logFilePath), true);
        }
        return [];
    }

    private function saveLogs(array $logs)
    {
        Storage::put($this->logFilePath, json_encode($logs, JSON_PRETTY_PRINT));
    }

    public function handle(Request $request)
    {
        $message = $request->input('data.message.text');
        $from = $request->input('data.phone_number');
        $device = $request->input('data.device_name');

        if ($message && $from) {
            $messageLogs = $this->getLogs();
            $newLog = [
                'id' => uniqid(),
                'phone' => $from,
                'message' => $message,
                'status' => 'received',
                'timestamp' => now()->timestamp,
                'type' => 'incoming', // Tambahkan tipe 'incoming'
            ];
            array_push($messageLogs, $newLog);
            $this->saveLogs($messageLogs);
        }

        // ... logika balasan otomatis (jika ada) ...

        return response()->json(['success' => true]);
    }
}
