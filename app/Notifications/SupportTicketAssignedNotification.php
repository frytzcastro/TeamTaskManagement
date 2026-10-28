<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SupportTicketAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(public SupportTicket $ticket) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'support_assigned',
            'message' => 'Support ticket assigned: '.$this->ticket->title,
            'support_ticket_id' => $this->ticket->id,
            'url' => route('support.show', $this->ticket),
        ];
    }
}
