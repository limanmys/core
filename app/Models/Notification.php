<?php

namespace App\Models;

use App\Casts\Jsonb;
use App\User;
use Illuminate\Database\Eloquent\Concerns\HasEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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

    public static function boot()
    {
        parent::boot();

        static::creating(function ($notification) {
            $notification->send_at = now();
        });
    }

    public static function send(
        string $level,
        string $template,
        array $contents = [],
        array|string $sendTo = 'all',
        bool $mail = false
    ) {
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

        return $object;
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'notification_users')
            ->withPivot('read_at');
    }

    public function scopeUnread($query)
    {
        return $query->whereHas('users', function ($query) {
            $query->whereNull('read_at');
        });
    }
}
