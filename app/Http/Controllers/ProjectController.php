<?php

namespace App\Http\Controllers;

use App\Enums\PublishState;
use App\Models\Project;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(): View {
        $projects = Project::query()
            ->where('publish_state_id', PublishState::PUBLISHED->value)
            ->limit(6)
            ->get();
        return view('projects.index', [
            'projects' => $projects
        ]);
    }

    public function show(Project $project) : \Illuminate\Contracts\View\View
    {
        if ($project->publish_state_id !== PublishState::PUBLISHED->value) {
            abort(404);
        }
        return view('projects.default', [
            'project' => $project
        ]);
    }

}
