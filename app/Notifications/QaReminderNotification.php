<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class QaReminderNotification extends Notification
{
    public function __construct(
        public string $title,
        public string $message,
        public string $url = '/notifications',
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{title: string, message: string, url: string}
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
        ];
    }
}
