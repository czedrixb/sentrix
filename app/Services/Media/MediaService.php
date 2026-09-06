<?php

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores uploaded files on the public disk.
 *
 * Every problem the previous upload path had is closed here: files go through
 * the Storage facade rather than being moved into the document root, names are
 * random rather than time()-based (which collided within the same second), and
 * nothing is written until validation has passed.
 */
class MediaService
{
    /**
     * Store a file under the given directory and return its relative path.
     */
    public function store(UploadedFile $file, string $directory): string
    {
        return $file->storeAs(
            $directory,
            Str::uuid()->toString().'.'.$file->extension(),
            'public'
        );
    }

    /**
     * Store several files, preserving order.
     *
     * @param  list<UploadedFile>  $files
     * @return list<string>
     */
    public function storeMany(array $files, string $directory): array
    {
        return array_map(fn (UploadedFile $file): string => $this->store($file, $directory), $files);
    }

    /**
     * Remove a stored file. Safe to call with a null or already-missing path.
     */
    public function delete(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    /**
     * Replace one file with another, removing the old one only once the new one
     * is safely written.
     */
    public function replace(?string $existingPath, UploadedFile $file, string $directory): string
    {
        $newPath = $this->store($file, $directory);

        $this->delete($existingPath);

        return $newPath;
    }
}
