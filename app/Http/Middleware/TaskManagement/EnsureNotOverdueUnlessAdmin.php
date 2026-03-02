<?php

namespace App\Http\Middleware\TaskManagement;

use Symfony\Component\HttpFoundation\Response;
use App\Models\TaskManagement\Task;
use Illuminate\Http\Request;
use Closure;

class EnsureNotOverdueUnlessAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return $next($request);
        }

        $task = $request->route('task');

        if (
            $task instanceof Task
            && $task->deadline !== null
            && $task->deadline->isPast()
            && $task->status !== 'done'
        ) {
            return response()->json([
                'message' => 'Only admins can edit tasks with an overdue deadline.',
            ], 403);
        }

        return $next($request);
    }
}
