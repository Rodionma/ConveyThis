<?php

namespace Tests\Feature;

use App\Models\StoredFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_pages_render(): void
    {
        $this->get('/')->assertOk()->assertSee('Upload documents');
        $this->get('/files')->assertOk()->assertSee('Uploaded files');
    }

    public function test_pdf_can_be_uploaded(): void
    {
        $upload = UploadedFile::fake()->createWithContent('report.pdf', "%PDF-1.4\n%test\n");

        $response = $this->postJson('/files', ['file' => $upload]);

        $response->assertCreated()->assertJsonPath('data.name', 'report.pdf');

        $file = StoredFile::firstOrFail();
        $this->assertSame('pdf', $file->extension);
        $this->assertSame('application/pdf', $file->mime_type);
        $this->assertTrue($file->expires_at->between(now()->addMinutes(1440)->subMinute(), now()->addMinutes(1440)->addMinute()));
        Storage::disk('local')->assertExists($file->path);
    }

    public function test_docx_can_be_uploaded(): void
    {
        $this->postJson('/files', ['file' => $this->fakeDocx('letter.docx')])
            ->assertCreated()
            ->assertJsonPath('data.extension', 'docx');
    }

    public function test_other_types_are_rejected(): void
    {
        $this->postJson('/files', ['file' => UploadedFile::fake()->create('image.png', 10, 'image/png')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');

        foreach (['fake.pdf', 'fake.docx'] as $name) {
            $tmp = tempnam(sys_get_temp_dir(), 'txt');
            file_put_contents($tmp, 'just some text');

            $this->postJson('/files', ['file' => new UploadedFile($tmp, $name, null, null, true)])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('file');
        }

        $this->assertDatabaseCount('stored_files', 0);
    }

    public function test_files_larger_than_limit_are_rejected(): void
    {
        $this->postJson('/files', ['file' => UploadedFile::fake()->create('big.pdf', 10241, 'application/pdf')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');
    }

    public function test_list_returns_active_files_only(): void
    {
        StoredFile::factory()->create(['original_name' => 'active.pdf']);
        StoredFile::factory()->expired()->create(['original_name' => 'old.pdf']);

        $this->getJson('/files/list')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'active.pdf');
    }

    public function test_file_can_be_downloaded(): void
    {
        $this->postJson('/files', ['file' => UploadedFile::fake()->createWithContent('a.pdf', "%PDF-1.4\n")]);
        $file = StoredFile::firstOrFail();

        $this->get(route('files.download', $file))
            ->assertOk()
            ->assertDownload('a.pdf');
    }

    private function fakeDocx(string $name): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'docx');
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
        $zip->addFromString('word/document.xml', '<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body/></w:document>');
        $zip->close();

        return new UploadedFile($tmp, $name, null, null, true);
    }
}
