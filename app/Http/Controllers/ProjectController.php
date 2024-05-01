<?php

namespace App\Http\Controllers;

use App\Enums\PublishState;
use App\Helpers\AppHelper;
use App\Models\CodeRelease;
use App\Models\Project;
use App\Services\Models\ViewService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProjectController extends Controller
{
    public function index(): View
    {
        AppHelper::resolve(ViewService::class)->associate(Project::class);
        $projects = Project::query()
            ->orderByDesc('created_at')
            ->limit(24)
            ->get();
        return view('projects.index', [
            'projects' => $projects
        ]);
    }

    public function show(Project $project): \Illuminate\Contracts\View\View
    {
        AppHelper::resolve(ViewService::class)->associate($project);
        $relatedItems = $project->projects->merge($project->pages)->sortByDesc('created_at');
        $relatingItems = $project->relatingProjects->sortByDesc('created_at');
        return view('projects.show', [
            'project' => $project,
            'relatedItems' => $relatedItems,
            'relatingItems' => $relatingItems,
        ]);
    }

    public function proxy(Project $project, string $path): Redirector|Application|RedirectResponse|BinaryFileResponse
    {
        $codeRelease = $project->codeReleases()->latest()->first();
        if (!($codeRelease instanceof CodeRelease)) {
            abort(404);
        }
        return (new CodeReleaseController())->proxy($codeRelease, $path);
    }

    public function proxyIndex(Project $project): Redirector|Application|RedirectResponse|BinaryFileResponse
    {
        $codeRelease = $project->codeReleases()->latest()->first();
        if (!($codeRelease instanceof CodeRelease)) {
            abort(404);
        }
        return redirect(route('projects.proxy', [
            'project' => $project->id,
            'path' => 'index.html'
        ]), 302);
    }

}
