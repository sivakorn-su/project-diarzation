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
            $path = $file->store($folder, 'public');
            $publicPath = '/storage/' . $path;
            $updateData['media_paths'] = $publicPath;
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
        set_time_limit(300); // 300 seconds = 5 minutes, adjust as needed

        $meetingInfo = MeetingInfo::where('meeting_id', $meeting->id)->first();

        if (!$meetingInfo || !$meetingInfo->media_paths) {
            return response()->json(['error' => 'No media file found.'], 404);
        }

        $filePath = storage_path('app/public/' . Str::after($meetingInfo->media_paths, '/storage/'));
        
        if (!file_exists($filePath)) {
            return response()->json(['error' => 'Media file does not exist.'], 404);
        }

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->request('POST', 'https://d127626c7864.ngrok-free.app/upload_video/', [
                'multipart' => [
                    [
                        'name'     => 'file',
                        'contents' => fopen($filePath, 'r'),
                        'filename' => basename($filePath),
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
}
