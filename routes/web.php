<?php

use App\Http\Controllers\MeetingController;
use App\Http\Controllers\MeetingInfoController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('auth/Login');
})->name('home');

Route::get('dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/meetings', [MeetingController::class, 'index'])->name('meetings');
    Route::get('/meetings/create', [MeetingController::class, 'create'])->name('meetings.create');
    Route::post('/meetings', [MeetingController::class, 'store'])->name('meetings.store');

    Route::get('/meetings/{meeting}', [MeetingController::class, 'show'])->name('meetings.show');

    Route::post('/meetings/{meeting}/infos', [MeetingInfoController::class, 'update'])->name('description.update');
    Route::post('/meetings/{meeting}/infos', [MeetingInfoController::class, 'transcript'])->name('transcript.update');
    Route::put('/meetings/{meeting}/transcript/update', [MeetingInfoController::class, 'transcriptUpdate'])->name('transcript.edit');

    Route::get('/meetings/{meeting}/edit', [MeetingController::class, 'edit'])->name('meetings.edit');
    Route::put('/meetings/{meeting}', [MeetingController::class, 'update'])->name('meetings.update');

    // Upload media page for meeting info
    Route::get('/meeting/{meeting}/upload-media', function ($meeting) {
        return Inertia::render('meeting/UploadMedia', [
            'meetingId' => (int) $meeting,
        ]);
    })->name('meeting.uploadMedia');

    Route::delete('/meetings/{meeting}', [MeetingController::class, 'destroy'])->name('meetings.destroy');
    Route::get('/phpinfo', function () {
        phpinfo();
    });
    Route::get('/status', function () {
        return Inertia::render('Status');
    })->name('status');
    Route::get('/check-upload-limit', function () {
        return [
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'ini_loaded_file' => php_ini_loaded_file(),
            'ini_scanned_files' => php_ini_scanned_files(),
        ];
    });
    
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
