<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class EntityCreated extends Notification implements ShouldQueue
{
    use Queueable;

    public string $entityType;
    public int $entityId;
    public array $meta;

    public function __construct(string $entityType, int $entityId, array $meta = [])
    {
        $this->entityType = $entityType;
        $this->entityId   = $entityId;
        $this->meta       = $meta;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'entity_type' => $this->entityType,
            'entity_id'   => $this->entityId,
            'meta'        => $this->meta,
            'message'     => ucfirst($this->entityType) . " #{$this->entityId} created.",
        ];
    }
}
