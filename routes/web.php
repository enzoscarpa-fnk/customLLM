<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\UserInstructionController;

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/', [ConversationController::class, 'index'])->name('home');

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
    Route::post('/instructions/toggle', [UserInstructionController::class, 'toggle'])->name('instructions.toggle');
});
