<?php

namespace App\Mail;

use App\Services\FileStorageService;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class FileDeletedMail extends Mailable
{
    public function __construct(public array $file) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'File deleted: '.$this->file['name']);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.file-deleted',
            with: [
                'file' => $this->file,
                'reasonText' => $this->file['reason'] === FileStorageService::REASON_EXPIRED
                    ? 'automatically, because its '.FileStorageService::ttlLabel().' retention period expired'
                    : 'manually by a user',
            ],
        );
    }
}
