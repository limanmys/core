<?php

namespace App\Observers;

use App\Models\Notification;
use App\Models\Server;

class ServerObserver
{
    /**
     * Listen to the Server created event.
     *
     * @param  \App\Server  $server
     * @return void
     */
    public function created(Server $server)
    {
        Notification::send(
            'information',
            'SERVER_CREATED',
            [
                'name' => $server->name,
            ],
            'admins',
            true
        );
    }

    /**
     * Listen to the Server deleted event.
     *
     * @param  \App\Server  $server
     * @return void
     */
    public function deleted(Server $server)
    {
        Notification::send(
            'information',
            'SERVER_DELETED',
            [
                'name' => $server->name,
            ],
            'admins',
            true
        );
    }
}
