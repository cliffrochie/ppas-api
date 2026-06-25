<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class NotificationService
{
    public function list(): LengthAwarePaginator
    {
        return Notification::latest()->paginate(15);
    }

    public function markRead(Notification $notification): Notification
    {
        return DB::transaction(function () use ($notification): Notification {
            $notification->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

            return $notification->refresh();
        });
    }
}
