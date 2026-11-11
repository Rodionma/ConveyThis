<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') · {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        .dropzone { border: 2px dashed #adb5bd; border-radius: .75rem; padding: 3rem 1rem; cursor: pointer; transition: .15s; }
        .dropzone.dragover, .dropzone:hover { border-color: #0d6efd; background: #f1f6ff; }
    </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="{{ route('files.create') }}"><i class="bi bi-file-earmark-lock"></i> {{ config('app.name') }}</a>
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link @if(request()->routeIs('files.create')) active @endif" href="{{ route('files.create') }}">Upload</a>
            </li>
            <li class="nav-item">
                <a class="nav-link @if(request()->routeIs('files.index')) active @endif" href="{{ route('files.index') }}">Manage files</a>
            </li>
        </ul>
    </div>
</nav>

<main class="container">
    @yield('content')
</main>

<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toasts"></div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/common.js') }}"></script>
@stack('scripts')
</body>
</html>
