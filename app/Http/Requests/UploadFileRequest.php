<?php

namespace App\Http\Requests;

use App\Rules\AllowedDocument;
use Illuminate\Foundation\Http\FormRequest;

class UploadFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'bail',
                'required',
                'file',
                'max:'.config('files.max_size_kb'),
                'extensions:'.implode(',', config('files.allowed_extensions')),
                new AllowedDocument(),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.extensions' => 'Only PDF and DOCX files are allowed.',
            'file.max' => 'The file may not be larger than '.(config('files.max_size_kb') / 1024).' MB.',
        ];
    }
}
