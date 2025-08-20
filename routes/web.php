<?php

use App\Http\Controllers\MeetingController;
use App\Http\Controllers\MeetingInfoController;
use App\Http\Controllers\TranscriptController;
use App\Http\Controllers\TranscriptSegmentsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Jobs\TestLogJob;

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
    Route::post('/meetings/{meeting}/transcript', [MeetingInfoController::class, 'transcript'])->name('transcript.update');
    Route::put('/meetings/{meeting}/transcript/update', [MeetingInfoController::class, 'transcriptUpdate'])->name('transcript.edit');
    // Export transcript as DOCX
    Route::get('/meetings/{meeting}/transcript/export-docx', [MeetingInfoController::class, 'transcriptExport'])->name('transcript.exportDocx');

    Route::get('/meetings/{meeting}/edit', [MeetingController::class, 'edit'])->name('meetings.edit');
    Route::put('/meetings/{meeting}', [MeetingController::class, 'update'])->name('meetings.update');

    Route::prefix('meetings/{meeting}/transcript/segments')->group(function () {
        Route::patch('{segment}', [TranscriptSegmentsController::class, 'update']); // แก้ไขราย segment
        Route::delete('{segment}', [TranscriptSegmentsController::class, 'destroy']); // ลบราย segment
    });

        // Transcript CRUD
    // Route::resource('transcripts', TranscriptController::class);

    // // เพิ่ม endpoint สำหรับสั่งประมวลผล/ถอดเสียง
    // Route::post('/transcripts/{transcript}/transcribe', [TranscriptController::class, 'transcribe'])
    //     ->name('transcripts.transcribe');

    // // Segment CRUD (ภายใน transcript)
    // Route::post('/transcripts/{transcript}/segments', [TranscriptSegmentController::class, 'store'])
    //     ->name('segments.store');

    // Route::put('/transcripts/{transcript}/segments/{segment}', [TranscriptSegmentController::class, 'update'])
    //     ->name('segments.update');

    // Route::delete('/transcripts/{transcript}/segments/{segment}', [TranscriptSegmentController::class, 'destroy'])
    // ->name('segments.destroy');
    
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
    Route::get('/queue-test', function () {
        TestLogJob::dispatch();
        return 'Job dispatched!';
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
