<?php

namespace App\Http\Middleware\TaskManagement;

use App\Models\TaskManagement\Task;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTaskOwnership
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return $next($request);
        }

        $task = $request->route('task');

        if ($task instanceof Task && $task->user_id !== $user->id) {
            return response()->json([
                'message' => 'You do not have permission to access this task.',
            ], 403);
        }

        return $next($request);
    }
}
