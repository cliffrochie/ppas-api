<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

final class AuditLogger
{
    /**
     * Write a single audit row.
     *
     * $oldValue / $newValue are cast to string (or left null) so that the
     * append-only `audit_logs` table always stores plain text regardless of
     * the originating column type (decimal, datetime, int, etc.).
     */
    public static function log(
        Model $auditable,
        string $event,
        string $field,
        mixed $oldValue,
        mixed $newValue,
        ?int $userId = null,
        ?string $ipAddress = null,
    ): void {
        AuditLog::create([
            'user_id'        => $userId,
            'auditable_type' => $auditable->getMorphClass(),
            'auditable_id'   => $auditable->getKey(),
            'event'          => $event,
            'field'          => $field,
            'old_value'      => $oldValue !== null ? (string) $oldValue : null,
            'new_value'      => $newValue !== null ? (string) $newValue : null,
            'ip_address'     => $ipAddress,
        ]);
    }

    /**
     * Write one audit row per changed field.
     *
     * $changes format: ['field_name' => ['old' => mixed, 'new' => mixed]]
     *
     * @param array<string, array{0: mixed, 1: mixed}> $changes
     */
    public static function logMany(
        Model $auditable,
        string $event,
        array $changes,
        ?int $userId = null,
        ?string $ipAddress = null,
    ): void {
        foreach ($changes as $field => [$old, $new]) {
            static::log($auditable, $event, $field, $old, $new, $userId, $ipAddress);
        }
    }
}
