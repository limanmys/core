<?php

namespace App\Models;

use App\Casts\Jsonb;
use App\User;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use UsesUuid;

    public $timestamps = false;

    protected $casts = [
        'contents' => Jsonb::class,
    ];

    protected $fillable = [
        'type',
        'template',
        'contents',
        'send_at'
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($notification) {
            $notification->send_at = now();
        });
    }

    public function send(
        string $type,
        string $template,
        array $contents = [],
        array|string $sendTo = "all"
    ) {
        $object = $this->create([
            'type' => $type,
            'template' => $template,
            'contents' => $contents,
        ]);

        if ($sendTo === "all") {
            $sendTo = User::all();
        }

        if ($sendTo === "admins") {
            $sendTo = User::admins()->get();
        }

        if ($sendTo === "non_admins") {
            $sendTo = User::nonAdmins()->get();
        } 

        if (is_array($sendTo) && isset($sendTo[0]) && $sendTo[0] instanceof User) {
            $sendTo = array_pluck($sendTo, 'id');
        }

        $object->users()->attach($sendTo);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'notification_users')
            ->withPivot('read_at');
    }

    public function scopeUnread($query)
    {
        return $query->whereDoesntHave('users', function ($query) {
            $query->where('user_id', auth()->id());
        });
    }
}
