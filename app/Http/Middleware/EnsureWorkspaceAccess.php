<?php

namespace App\Http\Middleware;

use App\Models\Workspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspaceAccess
{
    /**
     * Required role hierarchy: member < manager < owner.
     */
    private const HIERARCHY = ['member' => 1, 'manager' => 2, 'owner' => 3];

    public function handle(Request $request, Closure $next, string $minimumRole = 'member'): Response
    {
        $user = $request->user();
        $workspace = $request->route('workspace');

        if (! $user || ! $workspace instanceof Workspace) {
            abort(404);
        }

        $role = $user->workspaceRole($workspace);

        if ($role === null || self::HIERARCHY[$role] < self::HIERARCHY[$minimumRole]) {
            abort(403, 'Bạn không có quyền thực hiện hành động này.');
        }

        // Share the workspace with all views (sidebar).
        view()->share('currentWorkspace', $workspace);

        return $next($request);
    }
}
