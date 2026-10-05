<?php

namespace App\Http\Middleware;

use App\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProjectManager
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $project = $request->route('project');

        if (! $user || ! $project instanceof Project) {
            abort(404);
        }

        if (! $user->canManageWorkspace($project->workspace)) {
            abort(403, 'Chỉ Owner/Manager mới có quyền này.');
        }

        view()->share('currentWorkspace', $project->workspace);
        view()->share('currentProject', $project);

        return $next($request);
    }
}
