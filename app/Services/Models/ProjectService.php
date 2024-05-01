<?php

namespace App\Services\Models;

use App\Helpers\AppHelper;
use App\Models\CodeRelease;
use App\Models\Project;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Actions\Action;

class ProjectService
{
    public function release(
        Project      $project,
        UploadedFile $zip,
        ?string      $version = null,
        ?int         $completeness = null,
        ?int         $fun = null,
        ?int         $complexity = null
    ): CodeRelease
    {
        $last_release = $project->codeReleases()->latest()->first();
        if (!($last_release instanceof CodeRelease)) {
            $last_release = null;
        }
        if (empty($version)) {
            if ($last_release instanceof CodeRelease) {
                // increase semver by 1 patch version
                $version = $last_release->version;
                $version = explode('.', $version);
                $version[2] = (int)$version[2] + 1;
                $version = implode('.', $version);
            } else {
                // start at 0.0.1 if no previous release
                $version = '0.0.1';
            }
        } else {
            // validate semver
            if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
                throw new \InvalidArgumentException("Invalid Semver Version");
            }
            // make sure version number is higher than last release
            if ($last_release instanceof CodeRelease) {
                $last_version = $last_release->version;
                if (version_compare($version, $last_version, '<=')) {
                    throw new \InvalidArgumentException("Version must be higher than last release '$last_version'");
                }
            }
        }
        if (empty($completeness)) {
            $completeness = $last_release->completeness ?? 0;
        }
        if (empty($fun)) {
            $fun = $last_release->fun ?? 0;
        }
        if (empty($complexity)) {
            $complexity = $last_release->complexity ?? 0;
        }
        DB::transaction(function () use (
            $completeness, $fun, $complexity,
            $version, $project, $zip,
            &$release
        ) {
            $release = new CodeRelease();
            $release->version = $version;
            $release->completeness = $completeness;
            $release->fun = $fun;
            $release->complexity = $complexity;
            $project->codeReleases()->save($release);
            //
            $attachment = AppHelper::resolve(AttachmentService::class)
                ->createFromUploadedZipFile(
                    $zip,
                    "releases",
                    "releases",
                );
            $release->attachments()->save(
                $attachment,
                [
                    'relation' => 'code',
                ],
                true
            );
        });

        return $release;
    }
}
