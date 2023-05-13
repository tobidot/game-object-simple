<?php

namespace App\Http\Controllers;

use App\Enums\PublishState;
use App\Models\Project;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(): View {
        $projects = Project::query()
            ->limit(24)
            ->get();
        return view('projects.index', [
            'projects' => $projects
        ]);
    }

    public function show(Project $project) : \Illuminate\Contracts\View\View
    {
//        if ($project->publish_state_id !== PublishState::PUBLISHED->value  && !auth()->check()) {
//            abort(404);
//        }
        $relatedItems = $project->projects->merge($project->pages)->sortByDesc('created_at');
        $relatingItems = $project->relatingProjects->sortByDesc('created_at');
        return view('projects.show', [
            'project' => $project,
            'relatedItems' => $relatedItems,
            'relatingItems' => $relatingItems,
        ]);
    }

}
