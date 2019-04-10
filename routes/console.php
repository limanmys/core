<?php

use App\User;

Artisan::command('administrator',function (){
    // Check if Admin user is exists.
    $user = User::where('email','admin@liman.app')->first();
    if($user){
        if(!$this->confirm('Administrator kullanıcısı silinip tekrar eklenecektir. Devam etmek istiyor musunuz?')){
            return false;
        }
        $user->delete();
    }

    $password = \Illuminate\Support\Str::random();

    $user = User::create([
        'name' => "administrator",
        'email' => "admin@liman.app",
        'password' => Hash::make($password),
        'status' => "1"
    ]);
    $user->settings = [];
    $user->save();

    $perm = new \App\Permission();
    $perm->user_id = $user->_id;
    $perm->server = [];
    $perm->extension = [];
    $perm->script = [];
    $perm->save();
    $this->comment("Administrator kullanıcısı eklendi. ");
    $this->comment("Email  : admin@liman.app");
    $this->comment("Parola : " . $password . "");
})->describe('Create administrator account to use');
