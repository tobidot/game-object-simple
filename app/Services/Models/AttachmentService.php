<?php

namespace App\Services\Models;

use App\Enums\AttachmentType;
use App\Enums\PublishState;
use App\Exceptions\DummyException;
use App\Models\Attachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use function Illuminate\Filesystem\join_paths;

class AttachmentService
{
    /**
     * @throws DummyException
     */
    public function createFromUploadedZipFile(
        UploadedFile $file,
        string $prefix,
        ?string $file_name = null,
    ): Attachment {
        // Upload the zip file as an attachment
        $attachment = $this->createFromUploadedSingleFile($file, $prefix, $file_name);

        // unpack zip
        $disk = Storage::disk('public');
        $local_file_path = $disk->path($attachment->path);
        $local_folder_path = dirname($local_file_path);
        $zip = new \ZipArchive();
        if ($zip->open($local_file_path)) {
            $extracted = $zip->extractTo($local_folder_path);
            if ($extracted === false) {
                throw new DummyException("Could not unzip file");
            }
            $zip->close();
        }

        // simplify the folder structure
        $files = $disk->files($local_folder_path);
        $folders = $disk->directories($local_folder_path);
        if (count($files) === 1 && count($folders) === 1) {
            // if there is a single file (the zip) and a single folder now
            // move everything inside the folder to the root of the zip
            $top_level_folder = $folders[0];
            $inner_files = $disk->files($local_folder_path, true);
            foreach ($inner_files as $filepath) {
                $new_filepath = str_replace("/$top_level_folder", "", $filepath);
                $disk->move($filepath, $new_filepath);
            }
            $disk->delete($top_level_folder);
        }

        // make sure all files are public
        $disk->setVisibility($local_folder_path, 'public');
        $files = $disk->allFiles($local_folder_path);
        foreach ($files as $file) {
            $disk->setVisibility($file, 'public');
        }

        $count = count($files);
        Log::info("Attachment#$attachment->id unpacked from zip with $count files.");

        return $attachment;
    }


    public function createFromUploadedSingleFile(
        UploadedFile $file,
        string $prefix,
        ?string $file_name = null,
    ): Attachment {

        $disk = Storage::disk('public');
        $uuid = Str::uuid();
        $hash = hash_file('sha256', $file->getContent());
        $file_name = $file_name ?? $file->getClientOriginalName();
        $file_folder_path = "$uuid";
        $public_folder_path = join_paths("/", $prefix, $file_folder_path);
        $public_file_path = join_paths($public_folder_path, $file_name);

        // Deduplicate
        $existing = Attachment::query()
            ->where('hash', $hash)
            ->first();

        if ($existing) {
            $existing_content = $disk->get($public_file_path);
            if ($existing_content === $file->getContent()) {
                // I already uploaded the exact same file
                return $existing;
            }
        }

        // store the file
        $raw_file_path = $file->storeAs($public_folder_path, $file_name, [
            'disk' => 'public',
            'visibility' => 'public',
            'directory_visibility' => 'public'
        ]);
        if ($raw_file_path === false) {
            throw new DummyException("Failed to store file as attachment");
        }

        // figure out the type of file
        $mime_type = $file->getMimeType();
        $attachment_type = match ($mime_type) {
            'application/json',
            'text/html',
            'text/plain',
            => AttachmentType::TEXT,
            'image/png',
            'image/jpg',
            'image/jpeg',
            'image/gif',
            'image/svg+xml',
            'image/webp',
            'image/avif',
            => AttachmentType::IMAGE,
            'video/mp4',
            => AttachmentType::VIDEO,
            'application/zip',
            => AttachmentType::ZIP,
            default => AttachmentType::BINARY,
        };

        // Save the attachment
        $attachment = new Attachment();
        $attachment->path = $public_file_path;
        $attachment->url = join_paths('/storage', $attachment->path);
        $attachment->hash = $hash;
        $attachment->file_name = $file_name;
        $attachment->content_type = $mime_type;
        $attachment->publish_state = PublishState::PUBLISHED;
        $attachment->type = $attachment_type;
        $attachment->save();

        Log::info("Attachment#$attachment->id created for '$raw_file_path'");

        return $attachment;
    }

    public function getUrl(Attachment $attachment): ?string
    {
        if (!$attachment->file_name) {
            return null;
        }

        return asset(Storage::disk('public')->url($attachment->path));
    }

    public function getBaseUrl(Attachment $attachment): ?string
    {
        return dirname($this->getUrl($attachment));
    }
}
