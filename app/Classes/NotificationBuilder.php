<?php

namespace App\Classes;

use App\Models\Notification;
use Carbon\Carbon;

/**
 * Notification Builder
 *
 * This class converts database object of notification to readable format.
 */
class NotificationBuilder
{
    /**
     * @param Notification $notification
     */
    public function __construct(private $notification, private $locale)
    {
    }

    /**
     * Converts notification object to readable format.
     *
     * @return array
     */
    public function convertToBroadcastable(): array
    {
        switch ($this->notification->template) {
            case 'CUSTOM':
                $contents = $this->notification->contents;
                $title = $contents['title'];
                $content = $contents['content'];
                $locale = $this->locale;
                $fallback = env('APP_LOCALE', 'tr');

                if (isset($title[$locale])) {
                    $title = $title[$locale];
                } elseif (isset($title[$fallback])) {
                    $title = $title[$fallback];
                }

                if (isset($content[$locale])) {
                    $content = $content[$locale];
                } elseif (isset($content[$fallback])) {
                    $content = $content[$fallback];
                }

                break;
            default:
                $title = __('notification.' . $this->notification->template . '_title');
                $content = __('notification.' . $this->notification->template . '_content', $this->notification->contents);

                break;
        }

        Carbon::setLocale($this->locale ?: env('APP_LOCALE', 'tr'));

        $object = [
            'notification_id' => $this->notification->id,
            'title' => $title,
            'content' => $content,
            'level' => $this->notification->level,
            'send_at_humanized' => Carbon::parse($this->notification->send_at)->diffForHumans(),
            'send_at' => $this->notification->send_at,
        ];

        $notificationArray = $this->notification->toArray();
        if (
            isset($notificationArray['pivot']) &&
            isset($notificationArray['pivot']['read_at'])
        ) {
            $object['read_at'] = $notificationArray['pivot']['read_at'];
        } else {
            $object['read_at'] = null;
        }

        if (
            isset($notificationArray['pivot']) &&
            isset($notificationArray['pivot']['seen_at'])
        ) {
            $object['seen_at'] = $notificationArray['pivot']['seen_at'];
        } else {
            $object['seen_at'] = null;
        }

        return $object;
    }
}
