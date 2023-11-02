<?php

namespace App\Models;

use App\Casts\Jsonb;
use App\User;
use Illuminate\Database\Eloquent\Concerns\HasEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Notification Model
 */
class Notification extends Model
{
    use UsesUuid, HasEvents;

    public $timestamps = false;

    protected $casts = [
        'contents' => Jsonb::class,
    ];

    protected $fillable = [
        'id',
        'level',
        'template',
        'contents',
        'send_at',
        'mail'
    ];

    /**
     * Bootstrap the model and its traits.
     *
     * @return void
     */
    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($notification) {
            $notification->send_at = now();
        });
    }

    /**
     * Send notification with parameters
     *
     * @param string $level It might be information, success, warning, error
     * @param string $template CUSTOM or selected ones.
     * @param array $contents Contents
     * @param array|string $sendTo It might be all, admins, non_admins or an array with user ids.
     * @param bool $mail Send mail to recipients?
     * @return Notification
     */
    public static function send(
        string $level,
        string $template,
        array $contents = [],
        array|string $sendTo = 'all',
        bool $mail = false
    ): Notification
    {
        try {
            if ($sendTo === 'all') {
                $sendTo = User::all();
            }

            if ($sendTo === 'admins') {
                $sendTo = User::admins()->get();
            }

            if ($sendTo === 'non_admins') {
                $sendTo = User::nonAdmins()->get();
            }

            if (is_array($sendTo) && isset($sendTo[0]) && $sendTo[0] instanceof User) {
                $sendTo = array_pluck($sendTo, 'id');
            }

            $object = static::withoutEvents(function () use ($level, $template, $contents, $sendTo, $mail) {
                $temp = static::create([
                    'id' => (string) Str::uuid(),
                    'level' => $level,
                    'template' => $template,
                    'contents' => $contents,
                    'send_at' => now(),
                    'mail' => $mail,
                ]);
                $temp->users()->attach($sendTo);

                return $temp;
            });

            $object->fireModelEvent('created', false);
        } catch (\Throwable $e) {
            return new Notification();
        }

        return $object;
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'notification_users')
            ->withPivot('read_at');
    }
}
