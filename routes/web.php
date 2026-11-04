<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\AutoReplyController;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
})->name('landing');

Route::post('/verify-access', function (Request $request) {
    $correctKey = "sungaiaua2025";
    if ($request->key === $correctKey) {
        session(['access_granted' => true]);
        return response()->json(['success' => true]);
    }
    return response()->json(['success' => false], 401);
})->name('verify.access');

Route::middleware(['check.access'])->group(function () {
    Route::post('/logout', function (Request $request) {
        $request->session()->forget('access_granted');
        return redirect()->route('landing');
    })->name('logout');

    Route::get('/csrf-token', fn () => response()->json(['token' => csrf_token()]));

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/contacts', [DashboardController::class, 'contacts']);
    Route::get('/conversation/{phone}', [DashboardController::class, 'conversation'])->name('conversation');

    Route::post('/reply', [DashboardController::class, 'reply'])->name('reply');
    Route::post('/send-location', [DashboardController::class, 'sendLocation']);
    Route::post('/send-link', [DashboardController::class, 'sendLink']);
    Route::post('/send-group', [DashboardController::class, 'sendByGroup']);
    Route::post('/check-number', [DashboardController::class, 'checkNumber']);
    Route::post('/import-contacts', [DashboardController::class, 'importContacts']);

    Route::get('/schedule', [DashboardController::class, 'listSchedules']);
    Route::post('/schedule', [DashboardController::class, 'schedule']);
    Route::post('/schedule/cancel/{id}', [DashboardController::class, 'cancelSchedule']);

    // Dedicated Auto-Reply Management Page
    Route::get('/autoreply/manage', [AutoReplyController::class, 'index'])->name('autoreply.manage');
    Route::post('/autoreply/manage', [AutoReplyController::class, 'store'])->name('autoreply.store');
    Route::put('/autoreply/manage/{id}', [AutoReplyController::class, 'update'])->name('autoreply.update');
    Route::delete('/autoreply/manage/{id}', [AutoReplyController::class, 'destroy'])->name('autoreply.destroy');
    Route::post('/autoreply/manage/{id}/toggle', [AutoReplyController::class, 'toggle'])->name('autoreply.toggle');
    Route::post('/autoreply/manage/toggle-global', [AutoReplyController::class, 'toggleGlobal'])->name('autoreply.toggle_global');
    Route::post('/autoreply/manage/test', [AutoReplyController::class, 'test'])->name('autoreply.test');

    Route::get('/autoreply', fn () => response()->json(\App\Models\AutoReply::orderByDesc('id')->get()));
    Route::post('/autoreply', [DashboardController::class, 'saveAutoReply']);
    Route::delete('/autoreply/{id}', [DashboardController::class, 'deleteAutoReply']);

    Route::post('/settings', [DashboardController::class, 'saveSettings']);
    Route::get('/webhook/logs', [DashboardController::class, 'webhookLogs']);
    Route::post('/dashboard/sync', [DashboardController::class, 'syncMessages'])->name('dashboard.sync');

    Route::get('/usage', function () { return view('usage'); })->name('usage');

    Route::get('/report/contacts-messages', [DashboardController::class, 'exportContactsMessages'])->name('report.contacts_messages');
});

Route::post('/webhook/wablas', [WebhookController::class, 'handleIncoming'])
    ->name('webhook.wablas');

Route::post('/webhook/waha', [WebhookController::class, 'handleIncoming'])
    ->name('webhook.waha');

