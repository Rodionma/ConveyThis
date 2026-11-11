<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadFileRequest;
use App\Models\StoredFile;
use App\Services\FileStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    public function __construct(private readonly FileStorageService $service) {}

    public function create(): View
    {
        return view('files.upload', [
            'maxSizeKb' => config('files.max_size_kb'),
            'extensions' => config('files.allowed_extensions'),
            'ttlLabel' => FileStorageService::ttlLabel(),
        ]);
    }

    public function index(): View
    {
        return view('files.index', ['ttlLabel' => FileStorageService::ttlLabel()]);
    }

    public function list(): JsonResponse
    {
        $files = StoredFile::query()
            ->where('expires_at', '>', now())
            ->latest()
            ->get()
            ->map->toApiArray();

        return response()->json(['data' => $files]);
    }

    public function store(UploadFileRequest $request): JsonResponse
    {
        $file = $this->service->store($request->file('file'));

        return response()->json(['data' => $file->toApiArray()], 201);
    }

    public function download(StoredFile $file): StreamedResponse
    {
        abort_if($file->isExpired(), 404);

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    public function destroy(StoredFile $file): JsonResponse
    {
        $this->service->delete($file, FileStorageService::REASON_MANUAL);

        return response()->json(['message' => "File \"{$file->original_name}\" deleted."]);
    }
}
