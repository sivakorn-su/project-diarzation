<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class MediaStorage
{
    public static function storeWithFallback(UploadedFile $file): array
    {
        // 1) ลองเก็บลง public disk ก่อน
        try {
            $folder = str_starts_with($file->getMimeType(), 'video') ? 'videos' : 'audios';
            $path = $file->store($folder, 'public'); // ต้องมี storage:link
            if ($path) {
                return [
                    'driver' => 'public',
                    'path'   => Storage::disk('public')->url($path), // URL: /storage/xxx
                    'raw'    => $path,
                ];
            }
        } catch (\Throwable $e) {
            // noop → ไป supabase
        }

        // 2) Supabase fallback
        $supabaseUrl   = env('SUPABASE_URL');
        $supabaseToken = env('SUPABASE_SERVICE_ROLE');
        $bucket        = env('SUPABASE_BUCKET','media');

        if (!$supabaseUrl || !$supabaseToken) {
            throw new \RuntimeException('Local storage failed and Supabase is not configured.');
        }

        $filename = (str_starts_with($file->getMimeType(),'video') ? 'videos/' : 'audios/').$file->hashName();
        $uploadUrl = "{$supabaseUrl}/storage/v1/object/{$bucket}/{$filename}?upload=1";

        $res = Http::withHeaders([
            'Authorization' => 'Bearer '.$supabaseToken,
            'Content-Type'  => $file->getMimeType(),
        ])->withBody(fopen($file->getRealPath(), 'r'), $file->getMimeType())->put($uploadUrl);

        if ($res->failed()) throw new \RuntimeException('Upload to Supabase failed.');

        return [
            'driver' => 'supabase',
            'path'   => "{$supabaseUrl}/storage/v1/object/public/{$bucket}/{$filename}",
            'raw'    => $filename,
        ];
    }
}
