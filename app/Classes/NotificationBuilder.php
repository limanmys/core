<?php

namespace App\Classes;

use App\Models\Notification;
use Carbon\Carbon;

class NotificationBuilder
{
    public function __construct(private Notification $notification)
    {
    }

    public function convertToBroadcastable()
    {
        switch ($this->notification->template) {
            case 'CUSTOM':
                $contents = $this->notification->contents;
                $title = $contents['title'];
                $content = $contents['content'];
                $locale = app()->getLocale();
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
                $title = __('notification.'.$this->notification->template.'_title');
                $content = __('notification.'.$this->notification->template.'_content', $this->notification->contents);

                break;
        }

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
