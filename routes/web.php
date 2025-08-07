<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WebhookController;

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::post('/send-message', [DashboardController::class, 'sendMessage'])->name('send.message');
Route::post('/webhook', [WebhookController::class, 'handle']);
// Rute untuk menampilkan halaman inbox

// Rute untuk menampilkan percakapan spesifik dengan satu kontak
Route::get('/conversation/{phone}', [DashboardController::class, 'conversation'])->name('conversation');

// Rute untuk memproses balasan pesan
Route::post('/reply', [DashboardController::class, 'reply'])->name('reply.message');
