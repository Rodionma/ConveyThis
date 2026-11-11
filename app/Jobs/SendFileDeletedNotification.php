<?php

namespace App\Jobs;

use App\Mail\FileDeletedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendFileDeletedNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 60];

    public function __construct(public array $file) {}

    public function handle(): void
    {
        Mail::to(config('files.notification_email'))->send(new FileDeletedMail($this->file));
    }
}
