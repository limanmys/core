<?php

namespace Tests\Feature;

use App\Permission;
use App\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LoginPage extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    use RefreshDatabase;
    use WithoutMiddleware;
    public function testExample()
    {
        $response = $this->get('/giris');
        $response->assertSee('<b>Liman</b>');
        $response->assertStatus(200);

        $user = User::create([
            'name' => "administrator",
            'email' => "admin@liman.dev",
            'password' => Hash::make(Str::random()),
            'status' => "1"
        ]);
        $user->settings = [];
        $user->save();

        Permission::new($user->_id);
    }

    public function login(){

    }
}
