<?php

namespace App\Http\Middleware\TaskManagement;

use App\Models\TaskManagement\Task;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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
