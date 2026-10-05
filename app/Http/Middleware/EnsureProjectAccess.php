<?php

namespace App\Http\Middleware;

use App\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProjectAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $project = $request->route('project');

        if (! $user || ! $project instanceof Project) {
            abort(404);
        }

        $role = $user->workspaceRole($project->workspace);

        if ($role === null) {
            abort(404);
        }

        // Managers+ see every project in the workspace.
        // Members only see projects they participate in.
        $isManager = in_array($role, ['owner', 'manager']);
        $isProjectMember = $project->members()->where('users.id', $user->id)->exists();

        if (! $isManager && ! $isProjectMember) {
            abort(403, 'Bạn không tham gia project này.');
        }

        view()->share('currentWorkspace', $project->workspace);
        view()->share('currentProject', $project);

        return $next($request);
    }
}
