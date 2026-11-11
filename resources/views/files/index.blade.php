@extends('layouts.app')

@section('title', 'Manage files')

@section('content')
<div class="card shadow-sm">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Uploaded files</h1>
            <div>
                <button id="refresh" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
                <a href="{{ route('files.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-upload"></i> Upload</a>
            </div>
        </div>
        <p class="text-muted small">Files are removed automatically {{ $ttlLabel }} after upload. A notification is sent on every deletion.</p>

        <div class="table-responsive">
            <table class="table table-hover align-middle" id="files-table" data-list-url="{{ route('files.list') }}">
                <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th class="text-end">Size</th>
                    <th>Uploaded</th>
                    <th>Expires in</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                <tr><td colspan="6" class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm"></span> Loading…</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="delete-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete file</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">Are you sure you want to delete <strong id="delete-name"></strong>?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="delete-confirm">Delete</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/files.js') }}"></script>
@endpush
