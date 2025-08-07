<?php
// app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WablasService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    protected $wablasService;

    public function __construct(WablasService $wablasService)
    {
        $this->wablasService = $wablasService;
    }

    public function index()
    {
        // Panggil API Wablas untuk mendapatkan seluruh data report
        $report = $this->wablasService->getMessageReport(['perPage' => 500]); // Ambil cukup banyak data

        // Jika API berhasil
        if (isset($report['status']) && $report['status'] === true) {
            $messageLogs = collect($report['message'])->map(function ($log) {
                // Normalisasi data dari API ke format yang kita inginkan
                return [
                    'phone' => $log['phone']['from'], // Nomor pengirim
                    'message' => $log['text'],
                    'status' => $log['status'],
                    'type' => $log['type'] === 'agent' ? 'outgoing' : 'incoming',
                    'timestamp' => \Carbon\Carbon::parse($log['date']['created_at'])->timestamp,
                ];
            });
        } else {
            $messageLogs = new Collection();
        }

        // Hitung statistik dari data yang diambil
        $totalSent = $messageLogs->where('type', 'outgoing')->where('status', 'sent')->count();
        $totalReceived = $messageLogs->where('type', 'incoming')->count();
        $totalFailed = $messageLogs->where('type', 'outgoing')->where('status', 'failed')->count();

        // Ambil daftar kontak unik dari pesan masuk
        $uniqueContacts = $messageLogs->where('type', 'incoming')
            ->unique('phone')
            ->map(function ($item) {
                return [
                    'phone' => $item['phone'],
                    'latest_message' => $item['message'],
                    'timestamp' => $item['timestamp']
                ];
            })
            ->sortByDesc('timestamp');
        return view('dashboard.index', compact('totalSent', 'totalReceived', 'totalFailed', 'uniqueContacts'));
    }

    public function conversation(string $phone)
    {
        // Panggil API Wablas dengan filter nomor telepon
        $report = $this->wablasService->getMessageReport(['phone' => $phone]);

        if (isset($report['status']) && $report['status'] === true) {
            $conversationLogs = collect($report['message'])->map(function ($log) {
                return [
                    'phone' => $log['phone']['from'],
                    'message' => $log['text'],
                    'status' => $log['status'],
                    'type' => $log['type'] === 'agent' ? 'outgoing' : 'incoming',
                    'timestamp' => \Carbon\Carbon::parse($log['date']['created_at'])->timestamp,
                ];
            })->sortBy('timestamp');
        } else {
            $conversationLogs = new Collection();
        }

        return view('dashboard.conversation', compact('conversationLogs', 'phone'));
    }

    public function reply(Request $request)
    {
        $validatedData = $request->validate([
            'phone' => 'required|string|max:15',
            'message' => 'required|string',
        ]);

        $response = $this->wablasService->sendMessage($validatedData['phone'], $validatedData['message']);

        // Redirect kembali ke halaman percakapan
        if (isset($response['status']) && $response['status'] === true) {
            return redirect()->route('conversation', ['phone' => $validatedData['phone']])->with('success', 'Balasan berhasil dikirim!');
        } else {
            return redirect()->route('conversation', ['phone' => $validatedData['phone']])->with('error', 'Balasan gagal dikirim. Silakan cek log API.');
        }
    }
}
