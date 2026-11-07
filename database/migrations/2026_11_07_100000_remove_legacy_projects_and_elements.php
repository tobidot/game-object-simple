<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `tce_packages` is the only package system from here on: drops the legacy projects, code releases
 * and tobidot elements, and retires the four Baby Steps packages, which Spielwerk replaced.
 *
 * This removes rows only. The files stay on disk until `php artisan app:clean-storage --delete`.
 */
return new class extends Migration
{
    private const RETIRED_PACKAGES = [
        'tce-bs-00-barebones',
        'tce-bs-01-bouncing-text',
        'tce-bs-02-text-spawner',
        'tce-bs-03-text-collider',
    ];

    private const REMOVED_MODELS = [
        'App\Models\Project',
        'App\Models\CodeRelease',
        'App\Models\TobidotElement',
    ];

    public function up(): void
    {
        $this->retirePackages();
        $this->deleteRowsPointingAtRemovedModels();

        Schema::dropIfExists('tobidot_element_dependencies');
        Schema::dropIfExists('code_releases');
        Schema::dropIfExists('project_project');
        Schema::dropIfExists('page_project');
        Schema::dropIfExists('tobidot_elements');
        Schema::dropIfExists('projects');
    }

    /**
     * There is no way back but the backup taken before this ran.
     */
    public function down(): void
    {
        //
    }

    private function retirePackages(): void
    {
        $package_ids = DB::table('tce_packages')
            ->where('namespace', 'tobidot')
            ->whereIn('name', self::RETIRED_PACKAGES)
            ->pluck('id');

        DB::table('attachables')
            ->where('attachable_type', 'App\Models\TcePackage')
            ->whereIn('attachable_id', $package_ids)
            ->delete();

        // The dependency rows go with the packages (cascade).
        DB::table('tce_packages')->whereIn('id', $package_ids)->delete();
    }

    private function deleteRowsPointingAtRemovedModels(): void
    {
        DB::table('attachables')->whereIn('attachable_type', self::REMOVED_MODELS)->delete();
        DB::table('comments')->whereIn('commentable_type', self::REMOVED_MODELS)->delete();
        DB::table('views')->whereIn('viewable_type', self::REMOVED_MODELS)->delete();

        // Nova's action log: its lists resolve these classes, which no longer exist.
        DB::table('action_events')
            ->whereIn('actionable_type', self::REMOVED_MODELS)
            ->orWhereIn('target_type', self::REMOVED_MODELS)
            ->orWhereIn('model_type', self::REMOVED_MODELS)
            ->delete();
    }
};
