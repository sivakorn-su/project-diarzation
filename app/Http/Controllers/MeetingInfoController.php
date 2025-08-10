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
        if ($request->hasHeader('X-Inertia')) {
            $request->headers->set('Accept', 'application/json');
        }

        $data = $request->validate([
            'video' => 'required|file|mimetypes:video/mp4,video/quicktime,audio/mpeg,audio/wav|max:10240',
        ]);

        $meetingInfo = MeetingInfo::where('meeting_id', $meeting->id)->first();
        
        if ($request->hasFile('video')) {
            $file = $request->file('video');
            $type = explode('/', $file->getMimeType())[0];
            $folder = $type === 'video' ? 'videos' : 'audios';
            $filename = $folder . '/' . $file->hashName();

            // Supabase Config
            $supabaseUrl = env('SUPABASE_URL','https://kkrbjjtjpasqnwjawvpl.supabase.co');
            $supabaseToken = env('SUPABASE_SERVICE_ROLE');
            $bucket = env('SUPABASE_BUCKET', 'media');
            
            $uploadUrl = "{$supabaseUrl}/storage/v1/object/{$bucket}/{$filename}?upload=1";
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $supabaseToken,
                'Content-Type' => $file->getMimeType(),
            ])->withBody(
                fopen($file->getRealPath(), 'r'),
                $file->getMimeType()
            )->put($uploadUrl);

            if ($response->failed()) {
                return back()->withErrors(['video' => 'Upload to storage failed.'])->withInput();
            }
            
            $publicUrl = "{$supabaseUrl}/storage/v1/object/public/{$bucket}/{$filename}";
            $updateData['media_paths'] = $publicUrl;
        }
        
        $meetingInfo->update($updateData);
        
        return Inertia::location(url()->previous());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MeetingInfo $meetingInfo)
    {
        //
    }

    public function transcript(Meeting $meeting)
    {

        $meetingInfo = MeetingInfo::where('meeting_id', $meeting->id)->first();

        $tempDir = storage_path('app/temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        if (!$meetingInfo || !$meetingInfo->media_paths) {
            return response()->json(['error' => 'No media file found.'], 404);
        }

        $meetingInfo->update(['status' => 'processing']);
        ProcessMeetingTranscript::dispatch($meetingInfo->id)->onQueue('default');
       
        return Inertia::location(url()->previous());
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
                    'count_speaker'  => collect($counts)->map(fn($c,$s)=>['speaker'=>$s,'count'=>$c])->values()->all(),
                    'num_speakers'   => count($counts),
                    'speaker_array'  => array_values(array_keys($counts)),
                ];
            })($merged['data']);

            // อัปเดตค่าที่ต้องมีเสมอ
            $merged['total_sentence'] = $stats['total_sentence'];
            $merged['count_speaker']  = $stats['count_speaker'];
            $merged['num_speakers']   = $stats['num_speakers'];
            $merged['speaker_array']  = $stats['speaker_array'];

            // กันเผลอลบทิ้ง ถ้า client ไม่ส่งมา
            foreach (['audio_path','video_path','audio_length'] as $k) {
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
                    'num_speakers'   => $merged['num_speakers'],
                    'count_speaker'  => $merged['count_speaker'],
                    'total_sentence' => $merged['total_sentence'],
                    'transcript_json'=> $merged,
                ]);
            }

            // Inertia ฟลัชค่ากลับไป (อ่านได้จาก props.flash ใน FE)
            return back()->with([
                'num_speakers'   => $merged['num_speakers'],
                'count_speaker'  => $merged['count_speaker'],
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
        if (!$transcript || !isset($transcript['data'])) {
            return response()->json(['error' => 'No transcript data found.'], 404);
        }

        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        $section->addTitle('Meeting Transcript', 1);
        $section->addTextBreak(1);
        foreach ($transcript['data'] as $item) {
            $speaker = $item['speaker'] ?? 'Unknown';
            $start = $item['start'] ?? '';
            $end = $item['end'] ?? '';
            $text = $item['text'] ?? '';
            $section->addText("Speaker: $speaker");
            $section->addText("Time: $start - $end s");
            $section->addText($text, ['spaceAfter' => 200]);
            $section->addTextBreak(1);
        }

        $fileName = 'transcript_' . $meetingInfo->meeting_id . '_' . date('Ymd_His') . '.docx';
        $tempPath = storage_path('app/tmp/' . $fileName);
        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0777, true);
        }
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempPath);

        return response()->download($tempPath, $fileName)->deleteFileAfterSend(true);
    }
}
