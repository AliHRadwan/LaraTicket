<?php

namespace App\DTOs;

use App\Enums\NotificationType;
use Illuminate\Mail\Mailable;

readonly class NotificationDTO
{
    /**
     * @param NotificationType $type      Notification category
     * @param string           $title     Short heading (used in DB record and email subject fallback)
     * @param string           $body      Human-readable summary for the database channel / UI
     * @param array            $channels  Laravel notification channels (default: mail + database)
     * @param Mailable|null    $mailable  Pre-built Mailable for the email channel (rich template)
     * @param string|null      $actionUrl Optional CTA link (stored in DB, used in fallback email)
     * @param string|null      $actionText Optional CTA label
     * @param array            $meta      Arbitrary data stored alongside the DB notification
     */
    public function __construct(
        public NotificationType $type,
        public string $title,
        public string $body,
        public array $channels = ['mail', 'database'],
        public ?Mailable $mailable = null,
        public ?string $actionUrl = null,
        public ?string $actionText = null,
        public array $meta = [],
    ) {}
}
