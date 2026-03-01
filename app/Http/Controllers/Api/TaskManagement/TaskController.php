<?php

namespace App\Http\Controllers\Api\TaskManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskManagement\Task\StoreTaskRequest;
use App\Http\Requests\TaskManagement\Task\UpdateTaskRequest;
use App\Models\TaskManagement\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tasks = Task::with(['project'])
            ->forUser($request->user()->id)
            ->when($request->filled('project_id'), function ($query) use ($request) {
                $query->forProject($request->integer('project_id'));
            })
            ->latest()
            ->paginate(15);

        return response()->json($tasks);
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = Task::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Task created successfully.',
            'task'    => $task->load('project'),
        ], 201);
    }

    public function show(Task $task): JsonResponse
    {
        return response()->json($task->load(['user', 'project']));
    }

    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $task->update($request->validated());

        // todo evening firing for deadline

        return response()->json([
            'message' => 'Task updated successfully.',
            'task'    => $task->load('project'),
        ]);
    }

    public function destroy(Task $task): JsonResponse
    {
        $task->delete();

        return response()->json([
            'message' => 'Task deleted successfully.',
        ]);
    }

    public function overdue(Request $request): JsonResponse
    {
        $tasks = Task::with(['user', 'project'])
            ->overdue()
            ->when(! $request->user()->isAdmin(), function ($query) use ($request) {
                $query->forUser($request->user()->id);
            })
            ->latest('deadline')
            ->paginate(15);

        return response()->json($tasks);
    }

    public function byUser(Request $request, int $userId): JsonResponse
    {
        $tasks = Task::with(['project'])
            ->forUser($userId)
            ->latest()
            ->paginate(15);

        return response()->json($tasks);
    }

    public function byProject(Request $request, int $projectId): JsonResponse
    {
        $tasks = Task::with(['user'])
            ->forProject($projectId)
            ->when(! $request->user()->isAdmin(), function ($query) use ($request) {
                $query->forUser($request->user()->id);
            })
            ->latest()
            ->paginate(15);

        return response()->json($tasks);
    }
}
