<?php

use App\Http\Controllers\Api\TaskManagement\AuthController;
use App\Http\Controllers\Api\TaskManagement\ProjectController;
use App\Http\Controllers\Api\TaskManagement\TaskController;
use Illuminate\Support\Facades\Route;

// Version 1
Route::prefix('v1')->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });

    Route::middleware('auth:sanctum')->group(function () {

        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });

        Route::prefix('task-management')->group(function () {

            Route::get('tasks/overdue', [TaskController::class, 'overdue']);
            Route::get('tasks/by-user/{userId}', [TaskController::class, 'byUser']);
            Route::get('tasks/by-project/{projectId}', [TaskController::class, 'byProject']);

            Route::apiResource('tasks', TaskController::class)->only(['index', 'store']);

            Route::get('tasks/{task}', [TaskController::class, 'show'])
                ->middleware('task.owner');

            Route::put('tasks/{task}', [TaskController::class, 'update'])
                ->middleware(['task.owner', 'task.overdue']);

            Route::patch('tasks/{task}', [TaskController::class, 'update'])
                ->middleware(['task.owner', 'task.overdue']);

            Route::delete('tasks/{task}', [TaskController::class, 'destroy'])
                ->middleware('task.owner');

            Route::apiResource('projects', ProjectController::class);
        });
    });
});
