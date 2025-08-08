<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTranscriptRequest;
use App\Http\Requests\UpdateTranscriptRequest;
use App\Models\Transcript;
use App\Services\MediaStorage;
use App\Jobs\ProcessTranscript;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TranscriptController extends Controller
{
    public function index()
    {
        $items = Transcript::query()
            ->with(['segments' => fn($q)=>$q->select(['id','transcript_id','speaker'])])
            ->latest()->paginate(15);

        // ถ้าใช้ Inertia:
        return Inertia::render('transcripts/Index', ['items' => $items]);

        // return response()->json($items);
    }

    public function create()
    {
        // Inertia form page
        return Inertia::render('transcripts/Create');
        // return response()->noContent();
    }

    public function store(Request $request)
    {
        if ($request->hasHeader('X-Inertia')) $request->headers->set('Accept','application/json');

        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'media' => 'required|file|mimetypes:video/mp4,video/quicktime,audio/mpeg,audio/wav|max:10240',
        ]);

        $stored = MediaStorage::storeWithFallback($request->file('media'));

        $t = Transcript::create([
            'title'          => $data['title'] ?? null,
            'media_path'     => $stored['path'],
            'storage_driver' => $stored['driver'],
            'status'         => 'pending',
        ]);

        ProcessTranscript::dispatch($t->id)->onQueue('default');

        // Inertia:
        return Inertia::location(route('transcripts.show'));
        // return response()->json($t->fresh()->load(['segments'=>fn($q)=>$q->select(['id','transcript_id','speaker'])]), 201);
    }

    public function show(Transcript $transcript)
    {
        $transcript->load('segments');

        // Inertia:
        return Inertia::render('transcripts/Show', ['item'=>$transcript]);
        // return response()->json($transcript);
    }

    public function edit(Transcript $transcript)
    {
        $transcript->load('segments');
        
        return Inertia::render('transcripts/Edit', ['item'=>$transcript]);
        // return response()->json($transcript);
    }

    public function update(Request $request, Transcript $transcript)
    {
        $data = $request->validate(['title'=>'nullable|string|max:255']);
        $transcript->update($data);
        $transcript->load(['segments'=>fn($q)=>$q->select(['id','transcript_id','speaker'])]);

        // Inertia:
        return Inertia::location(route('transcripts.show',$transcript));
        // return response()->json($transcript);
    }

    public function destroy(Transcript $transcript)
    {
        $transcript->delete();

        // Inertia:
        return Inertia::location(route('transcripts.index'));
        // return response()->noContent();
    }

    public function transcribe(Transcript $transcript)
    {
        ProcessTranscript::dispatch($transcript->id)->onQueue('default');
        $transcript->load(['segments'=>fn($q)=>$q->select(['id','transcript_id','speaker'])]);
        // Inertia:
        return Inertia::location(route('transcripts.index'));
        // return response()->json(['queued'=>true,'transcript'=>$transcript]);
    }
}
