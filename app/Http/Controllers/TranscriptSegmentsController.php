<?php

namespace App\Http\Controllers;

use App\Models\TranscriptSegments;
use Illuminate\Http\Request;

class TranscriptSegmentsController extends Controller
{
    public function store(Request $request, Transcript $transcript)
    {
        $data = $request->validate([
            'speaker' => 'nullable|string|max:255',
            'filename'=> 'nullable|string|max:255',
            'start'   => 'nullable|numeric',
            'end'     => 'nullable|numeric',
            'avg_probability' => 'nullable|numeric',
            'text'    => 'nullable|string',
            'llm_corrected_text' => 'nullable|string',
        ]);

        $transcript->segments()->create($data);
        $transcript->refresh()->load(['segments'=>fn($q)=>$q->select(['id','transcript_id','speaker'])]);

        // num_speakers / count_speaker / total_sentence ถูกแปะแล้วจาก $appends
        return response()->json($transcript);
    }

    public function update(Request $request, Transcript $transcript, TranscriptSegment $segment)
    {
        abort_unless($segment->transcript_id === $transcript->id, 404);

        $data = $request->validate([
            'speaker' => 'nullable|string|max:255',
            'filename'=> 'nullable|string|max:255',
            'start'   => 'nullable|numeric',
            'end'     => 'nullable|numeric',
            'avg_probability' => 'nullable|numeric',
            'text'    => 'nullable|string',
            'llm_corrected_text' => 'nullable|string',
        ]);

        $segment->update($data);
        $transcript->refresh()->load(['segments'=>fn($q)=>$q->select(['id','transcript_id','speaker'])]);

        return response()->json($transcript);
    }

    public function destroy(Transcript $transcript, TranscriptSegment $segment)
    {
        abort_unless($segment->transcript_id === $transcript->id, 404);

        $segment->delete();
        $transcript->refresh()->load(['segments'=>fn($q)=>$q->select(['id','transcript_id','speaker'])]);

        return response()->json($transcript);
    }
}
