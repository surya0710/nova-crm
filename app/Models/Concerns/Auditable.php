<?php

namespace App\Models\Concerns;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            app(AuditLogger::class)->log($model, 'created');
        });

        static::updated(function (Model $model) {
            $changes = collect($model->getChanges())
                ->except(['updated_at', 'created_at'])
                ->all();

            if ($changes === []) {
                return;
            }

            if (isset($changes['status'])) {
                app(AuditLogger::class)->log($model, 'status_changed', [
                    'from' => $model->getOriginal('status'),
                    'to' => $changes['status'],
                ]);

                return;
            }

            if (isset($changes['assigned_to'])) {
                app(AuditLogger::class)->log($model, 'assigned', [
                    'from' => $model->getOriginal('assigned_to'),
                    'to' => $changes['assigned_to'],
                ]);

                return;
            }

            if (isset($changes['stage'])) {
                app(AuditLogger::class)->log($model, 'status_changed', [
                    'from' => $model->getOriginal('stage'),
                    'to' => $changes['stage'],
                ]);

                return;
            }

            app(AuditLogger::class)->log($model, 'updated', ['changes' => $changes]);
        });

        static::deleted(function (Model $model) {
            if (in_array(SoftDeletes::class, class_uses_recursive($model), true) && method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }

            app(AuditLogger::class)->log($model, 'deleted');
        });
    }
}
