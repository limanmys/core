<?php

use App\User;
use App\Permission;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

Artisan::command('administrator',function (){

    $password = Str::random();

    $user = User::where([
        "name" => "Administrator",
        "email" => "administrator@liman.app"
    ])->first();
    if($user){
        $user->update([
            "password" => Hash::make($password)
        ]);
    }else{
        $user = new User();
        $user->fill([
            "name" => "Administrator",
            "email" => "administrator@liman.app",
            "password" => Hash::make($password),
            "status" => 1,
        ]);
    }
    $user->save();

    $this->comment("Liman MYS Administrator Kullanıcısı");
    $this->comment("Email  : administrator@liman.app");
    $this->comment("Parola : " . $password . "");
})->describe('Create administrator account to use');