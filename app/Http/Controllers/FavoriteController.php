<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    /**
     * Toggle a project favorite for the user.
     */
    public function toggle(Request $request, Project $project): RedirectResponse
    {
        $favorite = Favorite::where('user_id', $request->user()->id)
            ->where('project_id', $project->id)
            ->first();

        if ($favorite) {
            $favorite->delete();

            return back()->with('status', 'Đã bỏ khỏi danh sách yêu thích.');
        }

        Favorite::create([
            'user_id' => $request->user()->id,
            'project_id' => $project->id,
        ]);

        return back()->with('status', 'Đã thêm "'.$project->name.'" vào yêu thích ⭐');
    }
}
