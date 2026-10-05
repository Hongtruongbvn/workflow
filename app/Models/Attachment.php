<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    protected $fillable = ['task_id', 'user_id', 'file_path', 'file_name', 'file_size', 'mime_type'];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function formattedSize(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 1).' '.$units[$i];
    }

    /**
     * Icon for the file type.
     */
    public function icon(): string
    {
        $mime = $this->mime_type ?? '';

        return match (true) {
            str_starts_with($mime, 'image/') => '🖼',
            str_contains($mime, 'pdf') => '📕',
            str_contains($mime, 'word') || str_contains($mime, 'document') => '📄',
            str_contains($mime, 'sheet') || str_contains($mime, 'excel') || str_contains($mime, 'csv') => '📊',
            str_contains($mime, 'zip') || str_contains($mime, 'compressed') => '🗜',
            str_contains($mime, 'text/') => '📃',
            default => '📎',
        };
    }
}
