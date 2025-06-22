<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\AskController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\UserInstructionController;

Route::get('/', function () {
    return redirect()->route('chat.index');
});

Route::get('/ask', [AskController::class, 'index'])->name('ask.index');
Route::post('/ask', [AskController::class, 'ask'])->name('ask.post');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/chat', [ConversationController::class, 'index'])->name('chat.index');
    Route::post('/chat', [ConversationController::class, 'store'])->name('chat.store');
    Route::get('/chat/{conversation}', [ConversationController::class, 'show'])->name('chat.show');
    Route::delete('/chat/{conversation}', [ConversationController::class, 'destroy'])->name('chat.destroy');
    Route::post('/chat/{conversation}/message', [ConversationController::class, 'addMessage'])->name('chat.message');
    Route::post('/chat/stream', [ConversationController::class, 'storeStream'])->name('chat.stream.store');
    Route::post('/chat/{conversation}/stream', [ConversationController::class, 'addMessageStream'])->name('chat.stream.message');

    // User Instructions Routes
    Route::post('/instructions', [UserInstructionController::class, 'store'])->name('instructions.store');
    Route::post('/instructions/update', [UserInstructionController::class, 'update'])->name('instructions.update');
    Route::delete('/instructions', [UserInstructionController::class, 'delete'])->name('instructions.delete');
    Route::delete('/instructions/command', [UserInstructionController::class, 'deleteCommand'])->name('instructions.deleteCommand');
    Route::post('/instructions/toggle', [UserInstructionController::class, 'toggle'])->name('instructions.toggle');
});
