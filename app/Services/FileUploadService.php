<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileUploadService
{
    public function upload(
        UploadedFile $file,
        string $folder
    ): string {

        return $file->store($folder, 'public');
    }

    public function delete(?string $path): void
    {
        if (!$path) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    public function url(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        return asset('storage/' . $path);
    }
}