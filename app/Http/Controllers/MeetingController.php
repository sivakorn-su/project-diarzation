<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMeetingRequest;
use App\Http\Requests\UpdateMeetingRequest;
use App\Models\Meeting;
use App\Models\MeetingInfo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MeetingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $meetings = Meeting::latest()->with(['user','info'])->get();

        return Inertia::render('meeting/Index', [
            'meetings' => $meetings,
            'authUser' => $request->user(),
            'flash' => session('success'),
        ]);
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
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $meeting = Meeting::create([
            'title' => $validated['title'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'user_id' => auth()->id(),
        ]);
        MeetingInfo::create(['meeting_id'=>$meeting->id]);

        return redirect()->route('meetings')->with('success', 'meetings created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request,Meeting $meeting)
    {
        $meeting->load(['user', 'info']);

        return Inertia::render('meeting/Show', [
            'meetings' => $meeting,
            'authUser' => $request->user(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Meeting $meeting)
    {
        $meeting->load(['user', 'info']);
        return Inertia::render('meeting/Edit', [
            'meeting' => $meeting,
            'authUser' => $request->user(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Meeting $meeting)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);
        $meeting->update($validated);

        return redirect()->route('meetings', $meeting->id)->with('success', 'Meeting updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Meeting $meeting)
    {
        $meeting->delete();

        return redirect()->route('meetings')->with('flash', 'Meeting deleted successfully.');
    }
}
