<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskTimeLogRequest;
use App\Http\Resources\TaskTimeLogResource;
use App\Models\Task;
use App\Models\TaskTimeLog;
use App\Services\TimeTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class TaskTimeLogController extends Controller
{
    public function __construct(protected TimeTrackingService $timeTracking) {}

    public function index(Task $task): AnonymousResourceCollection
    {
        $this->authorize('view', $task);

        return TaskTimeLogResource::collection(
            $task->timeLogs()->with('user')->latest('start_time')->paginate(request()->integer('per_page', 50))
        );
    }

    public function store(StoreTaskTimeLogRequest $request, Task $task): JsonResponse
    {
        try {
            $log = $this->timeTracking->logManual($task, $request->validated(), $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        $log->load('user');

        return (new TaskTimeLogResource($log))
            ->response()
            ->setStatusCode(201);
    }

    public function start(Task $task, Request $request): TaskTimeLogResource|JsonResponse
    {
        $this->authorize('timeLog', $task);

        try {
            $log = $this->timeTracking->startTimer(
                $task,
                $request->user(),
                $request->string('description')->toString() ?: null,
            );
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        $log->load('user');

        return new TaskTimeLogResource($log);
    }

    public function pause(Task $task, Request $request): TaskTimeLogResource|JsonResponse
    {
        $this->authorize('timeLog', $task);

        try {
            $log = $this->timeTracking->pauseTimer($task, $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        $log->load('user');

        return new TaskTimeLogResource($log);
    }

    public function resume(Task $task, Request $request): TaskTimeLogResource|JsonResponse
    {
        $this->authorize('timeLog', $task);

        try {
            $log = $this->timeTracking->resumeTimer($task, $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        $log->load('user');

        return new TaskTimeLogResource($log);
    }

    public function stop(Task $task, Request $request): TaskTimeLogResource|JsonResponse
    {
        $this->authorize('timeLog', $task);

        try {
            $log = $this->timeTracking->stopTimer($task, $request->user());
        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $e->errors(),
            ], 422);
        }

        $log->load('user');

        return new TaskTimeLogResource($log);
    }

    public function destroy(Task $task, TaskTimeLog $timeLog): JsonResponse
    {
        abort_unless((int) $timeLog->task_id === (int) $task->id, 404);
        $this->authorize('delete', $timeLog);

        $timeLog->delete();

        return response()->json(['success' => true]);
    }
}
