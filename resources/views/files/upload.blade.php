@extends('layouts.app')

@section('title', 'Upload')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">Upload documents</h1>
                <p class="text-muted">
                    Allowed: <strong>{{ strtoupper(implode(', ', $extensions)) }}</strong>,
                    up to <strong>{{ $maxSizeKb / 1024 }} MB</strong> per file.
                    Files are deleted automatically after <strong>{{ $ttlLabel }}</strong>.
                </p>

                <div id="dropzone" class="dropzone text-center"
                     data-upload-url="{{ route('files.store') }}"
                     data-max-size="{{ $maxSizeKb * 1024 }}"
                     data-extensions="{{ implode(',', $extensions) }}">
                    <i class="bi bi-cloud-arrow-up display-4 text-primary"></i>
                    <p class="mb-1 mt-2">Drag &amp; drop files here or <span class="text-primary">browse</span></p>
                    <small class="text-muted">Several files can be uploaded at once</small>
                    <input type="file" id="file-input" class="d-none" multiple
                           accept="{{ collect($extensions)->map(fn ($e) => '.'.$e)->implode(',') }}">
                </div>

                <ul id="upload-list" class="list-group mt-4"></ul>

                <div class="text-end mt-3">
                    <a href="{{ route('files.index') }}" class="btn btn-outline-primary">
                        <i class="bi bi-list-ul"></i> Go to file management
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/upload.js') }}"></script>
@endpush
