<?php

namespace App\Http\Controllers;

use App\Models\TranscriptSegments;
use Illuminate\Http\Request;
use App\Models\Meeting;
use Inertia\Inertia;
class TranscriptSegmentsController extends Controller
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
    public function show(TranscriptSegments $transcriptSegments)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TranscriptSegments $transcriptSegments)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Meeting $meeting, TranscriptSegments $segment)
    {
        
        $data = $request->validate([
            'start' => 'required',
            'end' => 'required',
            'speaker' => 'required|string',
            'text' => 'required|string',
            'is_remove' => 'nullable|boolean',
        ]);

        $segment->update($data);
        return Inertia::location(url()->previous());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Meeting $meeting,TranscriptSegments $segment)
    {
        $segment->delete();
        return Inertia::location(url()->previous());
    }
}
