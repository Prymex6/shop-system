<?php

namespace App\Traits;

use App\Services\AuditService;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::updated(function ($model) {
            $dirty = $model->getChanges();

            // Remove auto-updated timestamps from diff
            unset($dirty['updated_at']);

            if (empty($dirty)) {
                return;
            }

            $old = array_intersect_key($model->getOriginal(), $dirty);

            AuditService::log(
                strtolower(class_basename($model)) . '.updated',
                $model,
                $old,
                $dirty,
            );
        });

        static::deleted(function ($model) {
            AuditService::log(
                strtolower(class_basename($model)) . '.deleted',
                $model,
                $model->toArray(),
            );
        });
    }
}
