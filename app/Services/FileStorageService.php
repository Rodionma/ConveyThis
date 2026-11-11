<?php

namespace App\Services;

use App\Jobs\SendFileDeletedNotification;
use App\Models\StoredFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class FileStorageService
{
    public const REASON_MANUAL = 'manual';

    public const REASON_EXPIRED = 'expired';

    public static function ttlLabel(): string
    {
        $minutes = (int) config('files.ttl_minutes');

        return $minutes % 60 === 0
            ? trans_choice(':count hour|:count hours', intdiv($minutes, 60))
            : trans_choice(':count minute|:count minutes', $minutes);
    }

    public function store(UploadedFile $upload): StoredFile
    {
        $uuid = (string) Str::uuid();
        $extension = strtolower($upload->getClientOriginalExtension());
        $disk = config('files.disk');

        $path = $upload->storeAs(config('files.directory'), $uuid.'.'.$extension, $disk);

        if ($path === false) {
            throw new RuntimeException('Unable to store the uploaded file.');
        }

        try {
            return StoredFile::create([
                'uuid' => $uuid,
                'original_name' => $upload->getClientOriginalName(),
                'disk' => $disk,
                'path' => $path,
                'extension' => $extension,
                'mime_type' => $upload->getMimeType() ?? 'application/octet-stream',
                'size' => $upload->getSize(),
                'expires_at' => now()->addMinutes(config('files.ttl_minutes')),
            ]);
        } catch (\Throwable $e) {
            Storage::disk($disk)->delete($path);

            throw $e;
        }
    }

    public function delete(StoredFile $file, string $reason): void
    {
        $payload = [
            'id' => $file->uuid,
            'name' => $file->original_name,
            'size' => $file->size,
            'uploaded_at' => $file->created_at?->toIso8601String(),
            'deleted_at' => now()->toIso8601String(),
            'reason' => $reason,
        ];

        DB::transaction(function () use ($file, $payload) {
            $file->delete();

            SendFileDeletedNotification::dispatch($payload)
                ->onConnection(config('files.notification_connection'))
                ->onQueue(config('files.notification_queue'));
        });

        Storage::disk($file->disk)->delete($file->path);
    }

    public function purgeExpired(): int
    {
        $count = 0;

        StoredFile::query()->expired()->chunkById(100, function ($files) use (&$count) {
            foreach ($files as $file) {
                $this->delete($file, self::REASON_EXPIRED);
                $count++;
            }
        });

        return $count;
    }
}
