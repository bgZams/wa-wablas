<?php
// app/Services/WablasService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WablasService
{
    protected $baseUrl;
    protected $token;
    protected $secretKey;

    public function __construct()
    {
        $this->baseUrl = 'https://solo.wablas.com/api'; // URL endpoint baru
        $this->token = config('wablas.token');
        $this->secretKey = config('wablas.secret_key');
    }

    public function sendMessage($phone, $message, $isGroup = false)
    {
        // Siapkan data untuk payload
        $data = [
            'phone' => $phone,
            'message' => $message,
            'isGroup' => $isGroup ? 'true' : 'false',
        ];

        // Format payload sesuai dokumentasi (array of data)
        $payload = [
            'data' => [$data]
        ];
        $response = Http::withHeaders([
            'Authorization' => "{$this->token}.{$this->secretKey}",
            'Content-Type' => 'application/json'
        ])->post("https://solo.wablas.com/api/v2/send-message", $payload);

        return $response->json();
    }

    // Method baru untuk mengambil laporan pesan
    public function getMessageReport(array $params = [])
    {
        $response = Http::withHeaders([
            'Authorization' => "{$this->token}.{$this->secretKey}",
        ])->get("{$this->baseUrl}/report/message", $params);

        return $response->json();
    }
}
