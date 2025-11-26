<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMeetingInfoRequest;
use App\Http\Requests\UpdateMeetingInfoRequest;
use App\Models\Meeting;
use App\Models\MeetingInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use GuzzleHttp\Client;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Facades\Http;
use App\Jobs\ProcessMeetingTranscript;
use Aws\S3\S3Client;
use GuzzleHttp\Client as Guzzle;
use App\Jobs\TranscriptMeetingJob;
class MeetingInfoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(MeetingInfo $meetingInfo)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MeetingInfo $meetingInfo)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Meeting $meeting)
    {
        // if ($request->hasHeader('X-Inertia')) {
        //     $request->headers->set('Accept', 'application/json');
        // }

        // Laravel max เป็น KB → 3GB = 3,145,728 KB
        $data = $request->validate([
            'video' => 'required|file|mimetypes:video/mp4,video/quicktime,video/x-matroska,video/webm,audio/mpeg,audio/wav|max:3145728',
        ]);

        $meetingInfo = MeetingInfo::where('meeting_id', $meeting->id)->first();

        try {
            $updateData = [];

            if ($request->hasFile('video')) {
                $file = $request->file('video');

                $type = explode('/', $file->getMimeType())[0];
                $folder = $type === 'video' ? 'videos' : 'audios';

                $ext = $file->getClientOriginalExtension() ?: $file->extension();
                $key = "{$folder}/" . Str::uuid()->toString() . '.' . $ext;

                // อัปโหลดไป R2 (private แนะนำ)
                $stream = fopen($file->getRealPath(), 'r');
                Storage::disk('s3')->put($key, $stream, [
                    'visibility' => 'private',
                    'ContentType' => $file->getMimeType(),
                    'CacheControl' => 'public, max-age=31536000, immutable',
                ]);
                if (is_resource($stream))
                    fclose($stream);

                // เก็บเฉพาะ key
                $updateData['media_object_key'] = $key;
            }

            $meetingInfo->update($updateData);

            // Inertia Redirect OK
            return Inertia::location(url()->previous());
        } catch (\Throwable $e) {
            // กัน Inertia error: ส่งกลับเป็น redirect พร้อม error แทน JSON exception
            report($e);
            return back()->withErrors(['video' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MeetingInfo $meetingInfo)
    {
        //
    }

    public function transcript(Request $request, Meeting $meeting)
    {
        // รับ Inertia อย่างถูกต้อง (อย่าส่ง JSON error ใส่หน้า Inertia)
        if ($request->hasHeader('X-Inertia')) {
            $request->headers->set('Accept', 'application/json');
        }

        $meetingInfo = MeetingInfo::where('meeting_id', $meeting->id)->first();

        if (!$meetingInfo) {
            return back()->withErrors(['error' => 'No meeting info found.']);
        }

        // ✅ ใช้ object key เป็นหลัก (ปลอดภัยสุด)
        $key = $meetingInfo->media_object_key ?? null;

        // ถ้ายังไม่มี key จริง ๆ ค่อย fallback ไปใช้ media_paths (แต่แนะนำให้ย้ายมาเก็บ key)
        if (!$key && !$meetingInfo->media_paths) {
            return back()->withErrors(['error' => 'No media file found.']);
        }

        // อัปเดตสถานะ แล้วสั่งคิว
        $meetingInfo->update(['status' => 'processing']);

        TranscriptMeetingJob::dispatch($meetingInfo->id)
            ->onQueue('default');

        return back()->with('success', 'Transcription started.');
    }

    public function transcriptUpdate(Request $request, Meeting $meeting)
    {
        // รับ JSON มาเป็นสตริง
        $validated = $request->validate([
            'transcript_json' => 'required|json',
        ]);

        // แปลงเป็นอาเรย์
        $incoming = json_decode($validated['transcript_json'], true);
        if (!is_array($incoming)) {
            return $request->wantsJson()
                ? response()->json(['error' => 'Invalid transcript_json'], 422)
                : back()->withErrors(['error' => 'Invalid transcript_json']);
        }

        // หา/สร้าง record
        $meetingInfo = MeetingInfo::where('meeting_id', $meeting->id)->firstOrFail();

        // ของเดิม (ต้อง set casts ในโมเดล: 'transcript_json' => 'array')
        $current = $meetingInfo->transcript_json ?? [];

        // merge แบบ preserve ฟิลด์เดิม (เช่น media/info อื่นๆ)
        $merged = array_merge($current, $incoming);

        // ถ้ามี data ใหม่ให้ทับ, ถ้าไม่มีให้กัน null
        if (isset($incoming['data']) && is_array($incoming['data'])) {
            $merged['data'] = $incoming['data'];
        } else {
            $merged['data'] = $merged['data'] ?? [];
        }

        // คำนวณสถิติใหม่จาก data ปัจจุบัน
        $stats = (function (array $list) {
            $total = count($list);
            $counts = [];
            foreach ($list as $seg) {
                $sp = $seg['speaker'] ?? 'Unknown';
                $counts[$sp] = ($counts[$sp] ?? 0) + 1;
            }
            ksort($counts);
            return [
                'total_sentence' => $total,
                'count_speaker' => collect($counts)->map(fn($c, $s) => ['speaker' => $s, 'count' => $c])->values()->all(),
                'num_speakers' => count($counts),
                'speaker_array' => array_values(array_keys($counts)),
            ];
        })($merged['data']);

        // อัปเดตค่าที่ต้องมีเสมอ
        $merged['total_sentence'] = $stats['total_sentence'];
        $merged['count_speaker'] = $stats['count_speaker'];
        $merged['num_speakers'] = $stats['num_speakers'];
        $merged['speaker_array'] = $stats['speaker_array'];

        // กันเผลอลบทิ้ง ถ้า client ไม่ส่งมา
        foreach (['audio_path', 'video_path', 'audio_length'] as $k) {
            if (!array_key_exists($k, $merged) && array_key_exists($k, $current)) {
                $merged[$k] = $current[$k];
            }
        }

        // บันทึก (โมเดลควรมี casts: transcript_json => 'array')
        $meetingInfo->update([
            'transcript_json' => $merged,
        ]);

        // ถ้าขอเป็น JSON ส่งสถิติกลับให้ FE ใช้ต่อได้เลย
        if ($request->wantsJson()) {
            return response()->json([
                'num_speakers' => $merged['num_speakers'],
                'count_speaker' => $merged['count_speaker'],
                'total_sentence' => $merged['total_sentence'],
                'transcript_json' => $merged,
            ]);
        }

        // Inertia ฟลัชค่ากลับไป (อ่านได้จาก props.flash ใน FE)
        return back()->with([
            'num_speakers' => $merged['num_speakers'],
            'count_speaker' => $merged['count_speaker'],
            'total_sentence' => $merged['total_sentence'],
        ]);
    }


    public function transcriptExport(Meeting $meeting)
    {
        $meetingInfo = MeetingInfo::where('meeting_id', $meeting->id)->first();
        return $this->exportTranscriptDocx($meetingInfo);
    }

    /**
     * Export transcript_json to DOCX and return as download
     */
    public function exportTranscriptDocx(MeetingInfo $meetingInfo)
    {
        $transcript = $meetingInfo->transcript_json;
        if (is_string($transcript)) {
            $transcript = json_decode($transcript, true);
        }

        // Fallback: ถ้าไม่มี transcript_json หรือ data ว่าง ให้ดึงจาก Relation segments
        if (!$transcript || !isset($transcript['data']) || empty($transcript['data'])) {
            $segments = $meetingInfo->segments()
                ->orderBy('idx')
                ->get();

            if ($segments->isNotEmpty()) {
                $transcript = [
                    'data' => $segments->map(function ($s) {
                        return [
                            'start' => $s->start,
                            'end' => $s->end,
                            'speaker' => $s->speaker,
                            'text' => $s->text,
                            'is_remove' => (bool) $s->is_remove,
                        ];
                    })->toArray()
                ];
            }
        }

        if (!$transcript || !isset($transcript['data'])) {
            return response()->json(['error' => 'No transcript data found.'], 404);
        }

        $phpWord = new PhpWord();

        // Set default font settings
        $phpWord->setDefaultFontName('Sarabun'); // Use a font that supports Thai if possible, or fallback to Arial
        $phpWord->setDefaultFontSize(11);

        // Add Section
        $section = $phpWord->addSection([
            'marginTop' => 1440, // 1 inch
            'marginBottom' => 1440,
            'marginLeft' => 1440,
            'marginRight' => 1440,
        ]);

        // --- Header with Logo ---
        $header = $section->addHeader();
        $table = $header->addTable(['width' => 5000, 'unit' => 'pct', 'borderBottomSize' => 6]);
        $table->addRow();

        // Single Cell for Vertical Layout
        $cell = $table->addCell(10000);

        // Logo
        $logoPath = public_path('apple-touch-icon.png');
        if (file_exists($logoPath)) {
            $cell->addImage($logoPath, [
                'width' => 50,
                'height' => 50,
                'align' => 'center'
            ]);
        }

        // Title & Date
        $cell->addText('Meeting Transcript', ['bold' => true, 'size' => 18, 'color' => '333333'], ['align' => 'center', 'spaceAfter' => 0]);
        $cell->addText('Generated on: ' . date('d M Y H:i'), ['size' => 9, 'color' => '777777'], ['align' => 'center']);

        // --- Metadata Section ---
        $section->addTextBreak(1);
        $section->addText('Meeting Details', ['bold' => true, 'size' => 14, 'color' => '2E74B5']);
        $section->addText('ID: ' . $meetingInfo->meeting_id, ['size' => 10]);
        if ($meetingInfo->created_at) {
            $section->addText('Date: ' . $meetingInfo->created_at->format('d F Y, H:i'), ['size' => 10]);
        }
        if ($meetingInfo->meeting && $meetingInfo->meeting->user) {
            $section->addText('Created By: ' . $meetingInfo->meeting->user->name, ['size' => 10]);
        }
        $section->addTextBreak(1);

        // --- Transcript Content ---
        // Define styles
        $phpWord->addParagraphStyle('SpeakerPara', ['spaceBefore' => 120, 'spaceAfter' => 0, 'keepNext' => true]);
        $phpWord->addParagraphStyle('TextPara', ['spaceBefore' => 0, 'spaceAfter' => 240, 'alignment' => 'both']);

        foreach ($transcript['data'] as $item) {
            $speaker = $item['speaker'] ?? 'Unknown';
            $startSeconds = $item['start'] ?? 0;
            $endSeconds = $item['end'] ?? 0;
            $text = $item['text'] ?? '';
            $isRemove = !empty($item['is_remove']);

            // Format time
            $start = gmdate("H:i:s", (int) $startSeconds);
            $end = gmdate("H:i:s", (int) $endSeconds);

            // Speaker Line: "Speaker Name [00:00:00 - 00:00:10]"
            $textRun = $section->addTextRun('SpeakerPara');
            $textRun->addText($speaker, ['bold' => true, 'size' => 12, 'color' => '2E74B5']);
            $textRun->addText("  ");
            $textRun->addText("[$start - $end]", ['italic' => true, 'size' => 9, 'color' => '888888']);

            if ($isRemove) {
                $textRun->addText(" [REMOVED]", ['bold' => true, 'size' => 9, 'color' => 'FF0000']);
            }

            // Transcript Text
            $textStyle = ['size' => 11];
            if ($isRemove) {
                $textStyle['color'] = '999999';
                $textStyle['strikethrough'] = true;
            }
            $section->addText($text, $textStyle, 'TextPara');
        }

        // --- Summaries Section ---
        $summaries = $meetingInfo->summaries;
        if (!empty($summaries) && is_array($summaries)) {
            $section->addPageBreak();
            $section->addText('Meeting Summary', ['bold' => true, 'size' => 16, 'color' => '2E74B5']);
            $section->addTextBreak(1);

            foreach ($summaries as $key => $value) {
                // Skip empty values
                if (empty($value))
                    continue;

                // Format Header: key_points -> Key Points
                $headerTitle = ucwords(str_replace('_', ' ', $key));
                $section->addText($headerTitle, ['bold' => true, 'size' => 13, 'color' => '444444', 'underline' => 'single']);

                if (is_string($value)) {
                    $section->addText($value, ['size' => 11], 'TextPara');
                } elseif (is_array($value)) {
                    // Check if associative array (like participants) or list
                    $isAssoc = array_keys($value) !== range(0, count($value) - 1);

                    if ($isAssoc) {
                        foreach ($value as $subKey => $subValue) {
                            if (is_string($subValue)) {
                                $section->addText(ucwords(str_replace('_', ' ', $subKey)) . ": " . $subValue, ['size' => 11], 'TextPara');
                            }
                        }
                    } else {
                        foreach ($value as $item) {
                            if (is_string($item)) {
                                $section->addListItem($item, 0, null, 'multilevel');
                            } elseif (is_array($item)) {
                                // Handle complex objects like action items
                                $parts = [];
                                foreach ($item as $k => $v) {
                                    if (is_string($v) || is_numeric($v)) {
                                        $parts[] = ucwords(str_replace('_', ' ', $k)) . ": $v";
                                    }
                                }
                                if (!empty($parts)) {
                                    $section->addListItem(implode(' | ', $parts), 0, null, 'multilevel');
                                }
                            }
                        }
                    }
                }
                $section->addTextBreak(1);
            }
        }

        // --- Footer ---
        $footer = $section->addFooter();
        $footer->addPreserveText('Page {PAGE} of {NUMPAGES}', ['size' => 9, 'color' => 'AAAAAA'], ['align' => 'center']);

        // Save file
        $fileName = 'transcript_' . $meetingInfo->meeting_id . '_' . date('Ymd_His') . '.docx';
        $tempPath = storage_path('app/tmp/' . $fileName);

        // Ensure directory exists
        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0777, true);
        }

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempPath);

        return response()->download($tempPath, $fileName)->deleteFileAfterSend(true);
    }

    private function s3(): S3Client
    {
        return new S3Client([
            'version' => 'latest',
            'region' => env('AWS_REGION', 'auto'),
            'endpoint' => env('AWS_ENDPOINT'), // https://<ACCOUNT_ID>.r2.cloudflarestorage.com
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);
    }

    /**
     * 1) สร้าง object key + presigned PUT URL ให้ client อัปตรงไป R2
     */
    public function signPut(Request $req, Meeting $meeting)
    {
        $data = $req->validate([
            'filename' => 'required|string',
            'mime' => 'required|string',
        ]);

        $ext = pathinfo($data['filename'], PATHINFO_EXTENSION) ?: 'bin';
        $folder = str_starts_with($data['mime'], 'video/') ? 'media/videos' : 'media/audios';
        $key = "{$folder}/" . Str::uuid() . '.' . $ext;

        $s3 = $this->s3();

        $cmd = $s3->getCommand('PutObject', [
            'Bucket' => env('AWS_BUCKET'),
            'Key' => $key,
            'ContentType' => $data['mime'],
            'CacheControl' => 'public, max-age=31536000, immutable',
            'ACL' => 'private',
        ]);

        $presigned = $s3->createPresignedRequest($cmd, '+1 hour');

        return response()->json([
            'key' => $key,                     // << เก็บอันนี้ในฝั่ง client รอไว้
            'url' => (string) $presigned->getUri(),
            'headers' => ['Content-Type' => $data['mime']], // ต้องส่งหัวเดียวกันตอน PUT
        ]);
    }

    /**
     * 2) client อัปเสร็จ → เรียก confirm เพื่อตีหัว (head) ดูของจริง แล้วบันทึกลง DB
     */
    public function confirmPut(Request $req, Meeting $meeting)
    {
        $data = $req->validate([
            'key' => 'required|string',
        ]);

        $s3 = $this->s3();
        $head = $s3->headObject([
            'Bucket' => env('AWS_BUCKET'),
            'Key' => $data['key'],
        ]);

        // map ไปยัง MeetingInfo ของ meeting นี้
        $info = MeetingInfo::firstOrCreate(['meeting_id' => $meeting->id]);
        $info->media_object_key = $data['key'];                    // << save key
        $info->media_mime = $head['ContentType'] ?? null;
        $info->media_size = $head['ContentLength'] ?? null;
        $info->status = 'pending';                       // พร้อมกดถอดเสียง
        $info->save();

        // (ถ้า bucket public และมี base URL)
        $public = rtrim(env('R2_PUBLIC_BASE_URL', ''), '/');

        return response()->json([
            'ok' => true,
            'key' => $data['key'],
            'mediaUrl' => $public ? "{$public}/{$data['key']}" : null,
            'meetingId' => $meeting->id,
        ]);
    }
}
