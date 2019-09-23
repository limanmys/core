<?php

namespace App\Http\Controllers;

use App\Permission;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{

    public function one()
    {
        $user = User::where('id',auth()->id())->first();
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
        $pool = str_shuffle('abcdefghjklmnopqrstuvwxyzABCDEFGHJKLMNOPQRSTUVWXYZ234567890!$@%^&!$%^&');
        $password = substr($pool,0,10);

        // Create And Fill User Data
        $user = User::create([
            'name' => request('name'),
            'email' => request('email'),
            'password' => Hash::make($password),
            'status' => (request('type') == "administrator") ? "1" : "0",
            'forceChange' => true
        ]);
        $user->save();

        // Respond
        return respond("Kullanıcı Başarıyla Eklendi. Parola : " . $password,200);
    }

    public function remove()
    {
        // Delete Permissions
        Permission::where('user_id', request('user_id'))->delete();

        // Delete User
        User::where("id", request('user_id'))->delete();

        // Respond
        return respond("Kullanıcı Silindi",200);
    }

    public function passwordReset()
    {
        // Generate Password
        $pool = str_shuffle('abcdefghjklmnopqrstuvwxyzABCDEFGHJKLMNOPQRSTUVWXYZ234567890!$@%^&!$%^&');
        $password = substr($pool,0,10);

        $user = User::find(request('user_id'));
        $user->update([
            "password" => Hash::make($password),
            "forceChange" => true
        ]);
        return respond("Yeni Parola : " . $password,200);
    }

    public function selfUpdate()
    {
        if(!auth()->attempt([
            "email" => user()->email,
            "password" => request("old_password")
        ])){
            return respond("Eski Parolanız geçerli değil.",201);
        }
        $flag = Validator::make(request()->all(), [
            'password' => ['required', 'string', 
                'min:10','max:32','confirmed','regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{10,}$/'],
        ]);

        try{
            $flag->validate();
        }catch (\Exception $exception){
            return respond("Yeni parolanız en az 10 karakter uzunluğunda olmalı ve en az 1 sayı ve özel karakter içermelidir.",201);
        }

        auth()->user()->update([
           'name' => request('name'),
           'password' => Hash::make(request('password'))
        ]);

        auth()->logout();
        session()->flush();

        return respond('Kullanıcı Başarıyla Güncellendi, lütfen tekrar giriş yapın.',200);
    }

    public function adminUpdate()
    {
        $flag = Validator::make(request()->all(), [
            'username' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
        ]);

        try{
            $flag->validate();
        }catch (\Exception $exception){
            return respond("Girilen veri geçerli değil.",201);
        }

        $user = User::where("id", request('user_id'))->first();

        $user->update([
            'name' => request('username'),
            'email' => request('email'),
            'status' => request('status')
        ]);

        return respond('Kullanıcı Güncellendi.',200);
    }

    public function removeSetting()
    {
        $flag = DB::table('user_settings')->where([
            'user_id' => auth()->user()->id,
            'id' => request('setting_id')
        ])->delete();

        if($flag){
            return respond("Başarıyla silindi",200);
        }else{
            return respond("Başarıyla silinemedi",201);
        }
    }

    public function forcePasswordChange()
    {
        if(!auth()->attempt([
            "email" => user()->email,
            "password" => request("old_password")
        ])){
            return redirect()->route('password_change')->withErrors([
                "message" => "Eski Parolanız geçerli değil."
            ]);
        }
        $flag = Validator::make(request()->all(), [
            'password' => ['required', 'string', 
                'min:10','max:32','confirmed','regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{10,}$/'],
        ]);

        try{
            $flag->validate();
        }catch (\Exception $exception){
            return redirect()->route('password_change')->withErrors([
                "message" => "Yeni parolanız en az 10 karakter uzunluğunda olmalı ve en az 1 sayı ve özel karakter içermelidir."
            ]);
        }

        auth()->user()->update([
            'password' => Hash::make(request('password')),
            'forceChange' => false
         ]);
 
         auth()->logout();
         session()->flush();
         return redirect()->route('login')->withErrors([
            "message" => "Kullanıcı Başarıyla Güncellendi, lütfen tekrar giriş yapın."
        ]);
    }
}