<x-mail::message>
# File deleted

The file **{{ $file['name'] }}** was deleted {{ $reasonText }}.

<x-mail::table>
| | |
|:--|:--|
| File ID | {{ $file['id'] }} |
| Size | {{ number_format($file['size'] / 1024, 1) }} KB |
| Uploaded at | {{ $file['uploaded_at'] }} |
| Deleted at | {{ $file['deleted_at'] }} |
</x-mail::table>

{{ config('app.name') }}
</x-mail::message>
