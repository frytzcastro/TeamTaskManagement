<?php

namespace App\Notifications;

use App\Models\Release;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReleaseApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(public Release $release) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'release_approved',
            'message' => 'Release approved: '.$this->release->name,
            'release_id' => $this->release->id,
            'url' => route('releases.show', $this->release),
        ];
    }
}
