<?php

namespace App\Http\Controllers;

use App\Permission;
use App\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{

    public function one()
    {
        $user = User::where('_id',\Auth::id())->first();
        return view('users.one',[
            "user" => $user
        ]);
    }

    public function add()
    {
        $flag = Validator::make(request()->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
        ]);

        try{
            $flag->validate();
        }catch (\Exception $exception){
            return respond("Lütfen geçerli veri giriniz.",201);
        }

        // Check If user already exists.
        if(User::where('email',request('email'))->exists()){
            return respond("Bu email adresi ile ekli bir kullanıcı zaten var.",201);
        }

        // Generate Password
        $password = Str::random(8);

        // Create And Fill User Data
        $user = User::create([
            'name' => request('name'),
            'email' => request('email'),
            'password' => Hash::make($password),
            'settings' => [],
            'status' => (request('type') == "administrator") ? "1" : "0"
        ]);

        // Create Permissions For User
        Permission::new($user->_id);

        // Respond
        return respond("Kullanıcı Başarıyla Eklendi. Parola : " . $password,200);
    }

    public function remove()
    {
        // Delete Permissions
        Permission::where('user_id', request('user_id'))->delete();

        // Delete User
        User::find(request('user_id'))->delete();

        // Respond
        return respond("Kullanıcı Silindi",200);
    }

    public function passwordReset()
    {
        // Generate Password
        $password = Str::random(8);

        $user = User::find(request('user_id'))->update([
            "password" => Hash::make($password)
        ]);

        return respond("Yeni Parola : " . $password,200);
    }

}