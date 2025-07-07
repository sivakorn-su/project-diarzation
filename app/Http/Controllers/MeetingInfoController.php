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
    public function store(Request $request, Meeting $meeting)
    {
        $data = $request->validate([
            'transcript' => 'required|array',
            'video' => 'required|file|mimetypes:video/mp4,video/quicktime,audio/mpeg,audio/wav|max:10240', // 10MB per file (in KB)
        ]);
        
        $meetingInfo = MeetingInfo::where('meeting_id', $meeting->id)->first();
        if (!$meetingInfo) {
            $meetingInfo = MeetingInfo::create([
                'meeting_id' => $meeting->id,
                'transcript_json' => $data['transcript'],
            ]);
        } else {
            $meetingInfo->update([
                'transcript_json' => $data['transcript'],
            ]);
        }

        if ($request->hasFile('video')) {
            $meetingInfo->clearMediaCollection('media'); // remove old media if needed
            $meetingInfo->addMediaFromRequest('video')->toMediaCollection('media');
        }

        return Inertia::render('meeting/Show', [
            'meetings' => $meeting->load('info', 'user'),
            'authUser' => auth()->user(),
        ]);
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
        $data = $request->validate([
            'transcript' => 'required|array',
            'video' => 'required|file|mimetypes:video/mp4,video/quicktime,audio/mpeg,audio/wav|max:10240', // 10MB ต่อไฟล์ (หน่วย KB)
        ]);

        $meetingInfo = MeetingInfo::where('meeting_id', $meeting->id)->first();

        $updateData = [
            'transcript_json' => $data['transcript'],
        ];

        $meetingInfo->update($updateData);

        if ($request->hasFile('video')) {
            $meetingInfo->clearMediaCollection('media'); // remove old media if needed
            $meetingInfo->addMediaFromRequest('video')->toMediaCollection('media');
        }

        return Inertia::render('meeting/Show', [
            'meetings' => $meeting->load('info', 'user'),
            'authUser' => auth()->user(),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MeetingInfo $meetingInfo)
    {
        //
    }
}
