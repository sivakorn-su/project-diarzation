<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMeetingInfoRequest;
use App\Http\Requests\UpdateMeetingInfoRequest;
use App\Models\Meeting;
use App\Models\MeetingInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

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
        // Force JSON response for Inertia file upload
        if ($request->hasHeader('X-Inertia')) {
            $request->headers->set('Accept', 'application/json');
        }

        $data = $request->validate([
            'transcript' => 'nullable|string',
            'video' => 'nullable|file|mimetypes:video/mp4,video/quicktime,audio/mpeg,audio/wav|max:10240',
        ]);
        $meetingInfo = MeetingInfo::where('meeting_id', $meeting->id)->first();

        $updateData = [
            'transcript_json' => $data['transcript'],
        ];
        
        if ($request->hasFile('video')) {
            $file = $request->file('video');
            $type = explode('/', $file->getMimeType())[0];
            $folder = $type === 'video' ? 'videos' : 'audios';
            $path = $file->store($folder, 'public');
            $publicPath = '/storage/' . $path;
            $updateData['media_paths'] = $publicPath;
        }
        
        $meetingInfo->update($updateData);
        
        return back()->with('success', 'Upload successful');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MeetingInfo $meetingInfo)
    {
        //
    }
}
