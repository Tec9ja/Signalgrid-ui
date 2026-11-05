<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SignalGridEventNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $eventKey,
        public string $title,
        public string $message,
        public ?string $url = null,
        public array $context = [],
    ) {}

    public function via(object $notifiable): array
    {
        $preference = $notifiable->notificationPreferences()
            ->where('event_key', $this->eventKey)
            ->first();

        $channels = [];
        if ($preference?->database_enabled ?? true) $channels[] = 'database';
        if ($preference?->mail_enabled ?? false) $channels[] = 'mail';
        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event_key' => $this->eventKey,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'context' => $this->context,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject($this->title)->line($this->message);
        return $this->url ? $mail->action('Open SignalGrid', $this->url) : $mail;
    }
}
