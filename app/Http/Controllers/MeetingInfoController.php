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
            
            $uploadUrl = "{$supabaseUrl}/storage/v1/object/{$bucket}/{$filename}";
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $supabaseToken,
                'Content-Type' => $file->getMimeType(),
            ])->withBody(
                fopen($file->getRealPath(), 'r'), // 👈 stream raw file
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
        set_time_limit(1800); // 300 seconds = 5 minutes, adjust as needed

        $meetingInfo = MeetingInfo::where('meeting_id', $meeting->id)->first();

        if (!$meetingInfo || !$meetingInfo->media_paths) {
            return response()->json(['error' => 'No media file found.'], 404);
        }

        $mediaUrl = $meetingInfo->media_paths;
        $tempPath = storage_path('app/temp/' . basename($mediaUrl));
        
        if (!file_exists($tempPath)) {
            return response()->json(['error' => 'Media file does not exist.'], 404);
        }

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->request('POST', 'https://inwneon-project-voice-diarzation.hf.space/upload_video/', [
                'multipart' => [
                    [
                        'name'     => 'file',
                        'contents' => fopen($tempPath, 'r'),
                        'filename' => basename($tempPath),
                    ],
                ],
            ]);

            $result = json_decode($response->getBody(), true);

            if (!isset($result['data']) || !is_array($result['data']) || count($result['data']) === 0) {
                return response()->json(['error' => 'No transcript data received.'], 500);
            }

            $meetingInfo->update([
                'transcript_json' => $result,
            ]);

            unlink($tempPath);

            return Inertia::location(url()->previous());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Upload failed: ' . $e->getMessage()], 500);
        }
    }

    public function transcriptUpdate(Request $request, Meeting $meeting)
    {
        $data = $request->validate([
            'transcript_json' => 'required|json',
        ]);
        
        $meetingInfo = MeetingInfo::where('meeting_id', $meeting->id)->first();
        $meetingInfo->update($data);

        return Inertia::location(url()->previous());
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
