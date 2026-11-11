<?php

namespace Tests\Feature;

use App\Jobs\SendFileDeletedNotification;
use App\Mail\FileDeletedMail;
use App\Models\StoredFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Queue::fake();
    }

    private function makeFile(bool $expired = false): StoredFile
    {
        $factory = StoredFile::factory();
        $file = ($expired ? $factory->expired() : $factory)->create();
        Storage::disk('local')->put($file->path, 'content');

        return $file;
    }

    public function test_manual_delete_removes_file_and_publishes_notification_to_rabbitmq(): void
    {
        $file = $this->makeFile();

        $this->deleteJson(route('files.destroy', $file))->assertOk();

        $this->assertModelMissing($file);
        Storage::disk('local')->assertMissing($file->path);

        Queue::assertPushedOn('file-notifications', SendFileDeletedNotification::class,
            fn ($job) => $job->connection === 'rabbitmq'
                && $job->file['id'] === $file->uuid
                && $job->file['reason'] === 'manual');
    }

    public function test_deleting_unknown_file_returns_404(): void
    {
        $this->deleteJson('/files/00000000-0000-0000-0000-000000000000')->assertNotFound();
        Queue::assertNothingPushed();
    }

    public function test_purge_command_deletes_only_expired_files(): void
    {
        $expired = $this->makeFile(expired: true);
        $active = $this->makeFile();

        $this->artisan('files:purge-expired')
            ->expectsOutput('Deleted 1 expired file(s).')
            ->assertSuccessful();

        $this->assertModelMissing($expired);
        $this->assertModelExists($active);
        Storage::disk('local')->assertMissing($expired->path);
        Storage::disk('local')->assertExists($active->path);

        Queue::assertPushed(SendFileDeletedNotification::class, 1);
        Queue::assertPushed(SendFileDeletedNotification::class,
            fn ($job) => $job->file['id'] === $expired->uuid && $job->file['reason'] === 'expired');
    }

    public function test_file_is_purged_24_hours_after_upload(): void
    {
        $file = $this->makeFile();

        $this->travel(23)->hours();
        $this->artisan('files:purge-expired');
        $this->assertModelExists($file);

        $this->travel(1)->hours();
        $this->artisan('files:purge-expired');
        $this->assertModelMissing($file);
    }

    public function test_purge_is_scheduled(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('files:purge-expired')->assertSuccessful();
    }

    public function test_job_sends_email_to_configured_address(): void
    {
        config(['files.notification_email' => 'notify@example.test']);
        Mail::fake();

        (new SendFileDeletedNotification([
            'id' => 'abc', 'name' => 'doc.pdf', 'size' => 2048,
            'uploaded_at' => now()->toIso8601String(), 'deleted_at' => now()->toIso8601String(),
            'reason' => 'expired',
        ]))->handle();

        Mail::assertSent(FileDeletedMail::class, fn ($mail) => $mail->hasTo('notify@example.test')
            && $mail->file['name'] === 'doc.pdf');
    }

    public function test_mail_renders(): void
    {
        $mail = new FileDeletedMail([
            'id' => 'abc', 'name' => 'doc.pdf', 'size' => 2048,
            'uploaded_at' => now()->toIso8601String(), 'deleted_at' => now()->toIso8601String(),
            'reason' => 'manual',
        ]);

        $mail->assertSeeInHtml('doc.pdf');
        $mail->assertSeeInHtml('manually');
    }
}
