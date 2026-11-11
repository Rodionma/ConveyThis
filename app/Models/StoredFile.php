<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoredFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'original_name',
        'disk',
        'path',
        'extension',
        'mime_type',
        'size',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<=', now());
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->uuid,
            'name' => $this->original_name,
            'extension' => $this->extension,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'uploaded_at' => $this->created_at->toIso8601String(),
            'expires_at' => $this->expires_at->toIso8601String(),
            'download_url' => route('files.download', $this),
            'delete_url' => route('files.destroy', $this),
        ];
    }
}
