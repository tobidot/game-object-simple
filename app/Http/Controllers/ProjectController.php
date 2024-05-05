<?php

namespace App\Http\Controllers;

use App\Enums\PublishState;
use App\Helpers\AppHelper;
use App\Models\CodeRelease;
use App\Models\Project;
use App\Services\Models\ProjectService;
use App\Services\Models\ViewService;
use App\Traits\Filterable;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProjectController extends Controller
{
    use Filterable;

    /**
     * @throws ValidationException
     */
    public function index(): View
    {
        AppHelper::resolve(ViewService::class)->associate(Project::class);
        return $this->filterable(request()->all());

        $params = Validator::make(request()->all(), [
            'search' => ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9\s]*$/'],
        ])->validate();
        $base_url_path = route('projects');
        if (isset($params['search'])) {
            $base_url_path.= '?search=' . $params['search'];
        }
        $paginator = Project::query()
            ->orderByDesc('created_at', 'DESC')
            ->when(isset($params['search']), function ($query) use ($params) {
                $query->where('title', 'like', '%' . $params['search'] . '%');
            })
            ->paginate(9);
        $paginator->withPath($base_url_path);
        $paginator->onEachSide = 2;
        return view('projects.index', [
            'projects' => $paginator->items(),
            'paginator' => $paginator,
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

    /**
     * @throws ValidationException
     */
    public function release(Project $project): JsonResponse
    {
        $validator = Validator::make(request()->all(), [
            'zip' => ['required', 'file'],
            'version' => ['sometimes', 'string', 'regex:/^\d+\.\d+\.\d+$/', 'max:255'],
            'completeness' => ['sometimes', 'integer', 'min:0', 'max:10'],
            'fun' => ['sometimes', 'integer', 'min:0', 'max:10'],
            'complexity' => ['sometimes', 'integer', 'min:0', 'max:10'],
        ]);
        $params = $validator->validate();
        AppHelper::resolve(ProjectService::class)
            ->release(
                $project,
                $params['zip'],
                $params['version'] ?? null,
                $params['completeness'] ?? null,
                $params['fun'] ?? null,
                $params['complexity'] ?? null
            );
        return response()->json([
            'message' => 'Project released successfully',
            'version' => $project->codeReleases()->latest()->first()->version ?? '0.0.1',
            'redirect' => route('projects.proxy', ['project' => $project->id, 'path' => 'index.html'])
        ]);
    }

    public function getBaseQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Project::query();
    }

    public function getFilterViewName(): string
    {
        return 'projects.index';
    }

    public function getPerPage(): int
    {
        return 4;
    }

}
