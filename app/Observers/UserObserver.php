<?php

namespace App\Observers;

use App\Mail\Information;
use App\Models\Notification;
use App\User;
use Illuminate\Support\Facades\Mail;

class UserObserver
{
    /**
     * Listen to the User created event.
     *
     * @param  \App\User  $user
     * @return void
     */
    public function created(User $user)
    {
        try {
            if ((bool) env('MAIL_ENABLED', false)) {
                Mail::to($user->email)
                    ->send(new Information(
                        'Liman Merkezi Yönetim Sistemi üzerinde yeni hesabınız oluşturuldu. E-posta adresinizi ve sistem yöneticinizin size tanımladığı şifreyi kullanarak giriş yapabilirsiniz.',
                    ));
            }
            
            Notification::send(
                'information',
                'USER_CREATED',
                [
                    'name' => $user->name,
                ],
                'admins',
                false
            );
        } catch (\Exception $e) {}
    }

    /**
     * Listen to the User updating event.
     *
     * @param  \App\User  $user
     * @return void
     */
    public function updating(User $user)
    {
        if ($user->isDirty('password')) {
            try {
                if ((bool) env('MAIL_ENABLED', false)) {
                    Mail::to($user->getOriginal('email'))
                        ->send(new Information(
                            'Şifreniz ' . now()->isoFormat('LLLL') . ' tarihinde değiştirildi. Eğer bilginiz yoksa sistem yöneticinize başvurun.',
                        ));
                }

                Notification::send(
                    'warning',
                    'USER_PASSWORD_RESET',
                    [
                        'name' => $user->name,
                    ],
                    'admins',
                    true
                );
            } catch (\Exception $e) {}
        }

        if ($user->isDirty('email')) {
            try {
                if ((bool) env('MAIL_ENABLED', false)) {
                    Mail::to($user->getOriginal('email'))
                        ->send(new Information(
                            'E-posta adresiniz ' . now()->isoFormat('LLLL') . ' tarihinde ' . $user->email . ' olarak değiştirildi. Bilginiz yoksa sistem yöneticinize başvurun.',
                        ));
                }
            } catch (\Exception $e) {}
        }
    }
}
