<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMeetingInfoRequest;
use App\Http\Requests\UpdateMeetingInfoRequest;
use App\Models\Meeting;
use App\Models\MeetingInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
        $data = $request->validate([
            'transcript' => 'required|array'
        ]);
        $meetingInfo = MeetingInfo::where('meeting_id', $meeting->id)->first();
        $meetingInfo->update([
            'transcript_json' => $data['transcript'],
        ]);

        return redirect()->route('meetings.show', $meeting->id)->with('success', 'Meeting updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MeetingInfo $meetingInfo)
    {
        //
    }
}
