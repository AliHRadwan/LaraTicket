<?php

namespace App\Notifications;

use App\DTOs\NotificationDTO;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotificationSystem extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly NotificationDTO $dto) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->dto->channels;
    }

    /**
     * Delegates to the pre-built Mailable when provided (preserving the
     * rich Blade templates), otherwise falls back to a simple MailMessage.
     */
    public function toMail(object $notifiable): Mailable|MailMessage
    {
        if ($this->dto->mailable) {
            $recipient = $notifiable->routeNotificationFor('mail', $this);

            return $this->dto->mailable->to($recipient);
        }

        $message = (new MailMessage)
            ->subject($this->dto->title)
            ->line($this->dto->body);

        if ($this->dto->actionUrl) {
            $message->action($this->dto->actionText ?? 'View', $this->dto->actionUrl);
        }

        return $message;
    }

    /**
     * Structured payload persisted in the notifications table.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->dto->type->value,
            'title' => $this->dto->title,
            'body' => $this->dto->body,
            'action_url' => $this->dto->actionUrl,
            'action_text' => $this->dto->actionText,
            'meta' => $this->dto->meta,
        ];
    }
}
