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

class AttachmentService
{
    /**
     * @throws DummyException
     */
    public function createFromUploadedZipFile(
        UploadedFile $file,
        string       $path_prefix,
        string       $url_prefix,
    ): Attachment
    {
        $disk = Storage::disk('public');
        $uuid = Str::uuid();
        $zip_name = 'index.zip';
        $zip_base_name = substr($zip_name, 0, strpos($zip_name, '.'));
        $zip_folder_path = "$uuid";
        $disk_file_path = $file->storeAs("$path_prefix/$zip_folder_path", $zip_name, [
            'disk' => 'public',
            'visibility' => 'public',
            'directory_visibility' => 'public'
        ]);
        if ($disk_file_path === false) {
            throw new DummyException("Failed to store zip file as attachment");
        }
        // extract zip
        $local_file_path = $disk->path($disk_file_path);
        $local_folder_path = $disk->path("$path_prefix/$zip_folder_path");
        $zip = new \ZipArchive();
        if ($zip->open($local_file_path)) {
            $extracted = $zip->extractTo($local_folder_path);
            if ($extracted === false) {
                throw new DummyException("Could not unzip file");
            }
            $zip->close();
        };
        $files = $disk->files("$path_prefix/$zip_folder_path");
        $folders = $disk->directories("$path_prefix/$zip_folder_path");

        if (count($files) <= 1 && count($folders) === 1) {
            $top_level_folder = last(explode("/", $folders[0]));
            $inner_files = $disk->files("$path_prefix/$zip_folder_path", true);
            foreach ($inner_files as $filepath) {
                $new_filepath = str_replace("/$top_level_folder", "", $filepath);
                $disk->move($filepath, $new_filepath);
            }
            $disk->delete($folders[0]);
        }

        // make them public
        $disk->setVisibility($disk_file_path, 'public');
        $files = $disk->allFiles($disk_file_path);
        foreach ($files as $file) {
            $disk->setVisibility($file, 'public');
        }
        //
        $attachment = new Attachment();
        $attachment->url = '/' . implode("/", array_filter([
                $url_prefix,
                $zip_folder_path,
            ], fn($item) => !empty($item)));
        $attachment->path = '/' . implode("/", array_filter([
                $path_prefix,
                $zip_folder_path,
            ], fn($item) => !empty($item)));
        $attachment->publish_state_id = PublishState::PUBLISHED->value;
        $attachment->type_id = AttachmentType::ZIP->value;
        $attachment->save();
        return $attachment;
    }


    public function createFromUploadedSingleFile(
        UploadedFile $file,
        string       $path_prefix,
        string       $url_prefix,
    ): Attachment
    {
        $disk = Storage::disk('public');
        $uuid = Str::uuid();
        $file_name = "index.js";
        $file_folder_path = "$uuid";
        $disk_file_path = $file->storeAs("$path_prefix/$file_folder_path", $file_name, [
            'disk' => 'public',
            'visibility' => 'public',
            'directory_visibility' => 'public'
        ]);
        if ($disk_file_path === false) {
            throw new DummyException("Failed to store file as attachment");
        }

        // make them public
        $disk->setVisibility($disk_file_path, 'public');
        //
        $attachment = new Attachment();
        $attachment->url = '/' . implode("/", array_filter([
                $url_prefix,
                $file_folder_path,
            ], fn($item) => !empty($item)));
        $attachment->path = '/' . implode("/", array_filter([
                $path_prefix,
                $file_folder_path,
            ], fn($item) => !empty($item)));
        $attachment->publish_state_id = PublishState::PUBLISHED->value;
        $attachment->type_id = AttachmentType::BINARY->value;
        $attachment->save();
        return $attachment;
    }
}
