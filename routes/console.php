<?php

use App\User;
use App\Permission;
Artisan::command('administrator',function (){

    $password = \Illuminate\Support\Str::random();

    $user = new User();
    $user->fill([
        "name" => "Administrator",
        "email" => "administrator@liman.app",
        "password" => \Illuminate\Support\Facades\Hash::make($password),
        "status" => 1,
    ]);

    $user->save();

    $this->comment("Administrator kullanıcısı eklendi. ");
    $this->comment("Email  : administrator@liman.app");
    $this->comment("Parola : " . $password . "");
})->describe('Create administrator account to use');

Artisan::command("create:db",function(){

});