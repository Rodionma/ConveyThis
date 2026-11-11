<?php

namespace Database\Factories;

use App\Models\StoredFile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class StoredFileFactory extends Factory
{
    protected $model = StoredFile::class;

    public function definition(): array
    {
        $uuid = (string) Str::uuid();

        return [
            'uuid' => $uuid,
            'original_name' => $this->faker->word().'.pdf',
            'disk' => config('files.disk'),
            'path' => config('files.directory').'/'.$uuid.'.pdf',
            'extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'size' => $this->faker->numberBetween(1_000, 1_000_000),
            'expires_at' => now()->addMinutes(config('files.ttl_minutes')),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'created_at' => now()->subMinutes(config('files.ttl_minutes') + 1),
            'expires_at' => now()->subMinute(),
        ]);
    }
}
