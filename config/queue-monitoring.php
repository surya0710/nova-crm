<?php

return [
    'enabled' => (bool) env('QUEUE_MONITORING_ENABLED', true),

    'database_connection' => env('QUEUE_MONITORING_DB_CONNECTION'),

    'worker_stale_after_seconds' => (int) env('QUEUE_MONITORING_WORKER_STALE_AFTER', 90),

    'health_window_minutes' => (int) env('QUEUE_MONITORING_HEALTH_WINDOW', 60),

    'busy_pending_threshold' => (int) env('QUEUE_MONITORING_BUSY_PENDING', 100),

    'degraded_failed_threshold' => (int) env('QUEUE_MONITORING_DEGRADED_FAILED', 1),

    'scheduler_stale_after_seconds' => (int) env('SCHEDULER_STALE_AFTER', 180),
];
