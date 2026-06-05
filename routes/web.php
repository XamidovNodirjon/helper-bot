<?php

use Illuminate\Support\Facades\Route;
use Telegram\Bot\Laravel\Facades\Telegram;
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\EditorController;
use App\Http\Controllers\AdminController;


Route::get('/', function () {
    return view('welcome');
});

Route::get('setwebhook', function () {
    $url = env('TELEGRAM_WEBHOOK_URL');
    $response = Telegram::setWebhook(['url' => $url]);
    return $response;
});

Route::get('webapp/{telegramId}', [TelegramController::class, 'webApp'])->name('webapp');

// Web IDE routes
Route::get('editor', [EditorController::class, 'index'])->name('editor.index');
Route::get('editor/api/files', [EditorController::class, 'getFiles'])->name('editor.api.files');
Route::get('editor/api/file', [EditorController::class, 'getFile'])->name('editor.api.file');
Route::post('editor/api/save', [EditorController::class, 'saveFile'])->name('editor.api.save');
Route::post('editor/api/run-command', [EditorController::class, 'runCommand'])->name('editor.api.run-command');

// Admin panel authentication & actions
Route::get('admin/login', [AdminController::class, 'showLogin'])->name('login');
Route::post('admin/login', [AdminController::class, 'login']);
Route::post('admin/logout', [AdminController::class, 'logout'])->name('admin.logout');

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/users', [AdminController::class, 'usersList'])->name('users');
    Route::get('/users/{id}', [AdminController::class, 'userDetail'])->name('users.detail');
    Route::post('/users/{id}/toggle-ban', [AdminController::class, 'toggleBan'])->name('users.toggle-ban');
    Route::post('/users/{id}/toggle-sub', [AdminController::class, 'toggleSubscription'])->name('users.toggle-sub');
    Route::post('/users/{id}/message', [AdminController::class, 'sendDirectMessage'])->name('users.message');
    Route::post('/broadcast', [AdminController::class, 'broadcast'])->name('broadcast');
});