<?php

namespace App\Services;

use App\Models\ApplicationDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PrivateDocumentStorage
{
    public function store(UploadedFile $file, int $applicationId): array
    {
        $disk = config('scholarlink.document_storage');
        $path = 'documents/'.$applicationId.'/'.Str::uuid().'.'.strtolower($file->getClientOriginalExtension());
        if ($disk === 'supabase') {
            $this->assertPrivateBucket();
            $response = $this->client()->withBody(file_get_contents($file->getRealPath()), $file->getMimeType())
                ->post($this->objectUrl($path));
            if (! $response->successful()) $this->unavailable();
        } elseif ($disk === 'local') {
            if (! Storage::disk('local')->put($path, file_get_contents($file->getRealPath()))) $this->unavailable();
        } else {
            $this->unavailable();
        }
        return ['file_path' => $path, 'storage_disk' => $disk];
    }

    public function assertPrivateBucket(): void
    {
        $response = $this->client()->get($this->base().'/bucket/'.rawurlencode(config('scholarlink.supabase.bucket')));
        if (! $response->successful() || $response->json('public') !== false) $this->unavailable();
    }

    public function download(ApplicationDocument $document)
    {
        $name = preg_replace('/[^\pL\pN._ -]+/u', '_', $document->document_name);
        $name = trim($name ?: 'document', " ._-/\\") ?: 'document';
        $name .= '.'.preg_replace('/[^a-z0-9]/i', '', $document->file_type);
        $headers = ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'];
        if ($document->storage_disk === 'local') {
            abort_unless(Storage::disk('local')->exists($document->file_path), 404);
            return Storage::disk('local')->download($document->file_path, $name, $headers);
        }
        abort_unless($document->storage_disk === 'supabase', 503);
        $this->assertPrivateBucket();
        $response = $this->client()->get($this->objectUrl($document->file_path, true));
        if ($response->status() === 404) abort(404);
        if (! $response->successful()) $this->unavailable();
        // Proxy only after controller authorization; never expose a service key or object path.
        return response()->streamDownload(fn () => print($response->body()), $name, $headers);
    }

    private function client()
    {
        if (! config('scholarlink.supabase.key')) $this->unavailable();
        return Http::withToken(config('scholarlink.supabase.key'))
            ->withHeaders(['apikey' => config('scholarlink.supabase.key')])
            ->connectTimeout(10)->timeout(30);
    }

    private function base(): string
    {
        $url = rtrim((string) config('scholarlink.supabase.url'), '/');
        if (! str_starts_with($url, 'https://')) $this->unavailable();
        return $url.'/storage/v1';
    }

    private function objectUrl(string $path, bool $authenticated = false): string
    {
        return $this->base().'/object/'.($authenticated ? 'authenticated/' : '')
            .rawurlencode(config('scholarlink.supabase.bucket')).'/'
            .implode('/', array_map('rawurlencode', explode('/', $path)));
    }

    private function unavailable(): never
    {
        throw ValidationException::withMessages(['file' => 'Private storage is unavailable or the bucket is not private. Please try again or contact support.']);
    }
}
