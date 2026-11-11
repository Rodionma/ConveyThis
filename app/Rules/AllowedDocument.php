<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use ZipArchive;

class AllowedDocument implements ValidationRule
{
    private const DOCX_MIME = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('The file failed to upload.');

            return;
        }

        $extension = strtolower($value->getClientOriginalExtension());
        $mime = $value->getMimeType();

        $valid = match ($extension) {
            'pdf' => $mime === 'application/pdf',
            'docx' => $mime === self::DOCX_MIME
                || (in_array($mime, ['application/zip', 'application/octet-stream'], true) && $this->isDocxArchive($value->getRealPath())),
            default => false,
        };

        if (! $valid) {
            $fail('Only PDF and DOCX files are allowed.');
        }
    }

    private function isDocxArchive(string $path): bool
    {
        $zip = new ZipArchive();

        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            return false;
        }

        $isDocx = $zip->locateName('[Content_Types].xml') !== false
            && $zip->locateName('word/document.xml') !== false;

        $zip->close();

        return $isDocx;
    }
}
