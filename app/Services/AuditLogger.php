<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;

class AuditLogger
{
    private static ?AuditLog $model = null;

    private static function getModel(): AuditLog
    {
        if (self::$model === null) {
            self::$model = new AuditLog();
        }
        return self::$model;
    }

    /**
     * Record an audit log event
     */
    public static function log(
        string $action,
        ?string $description = null,
        ?array $metadata = null,
        ?int $userId = null
    ): int {
        try {
            return self::getModel()->log($action, $description, $userId, $metadata);
        } catch (\Throwable $e) {
            // Fail silently so audit log errors don't crash main business flows
            error_log("AuditLog error: " . $e->getMessage());
            return 0;
        }
    }
}
