<?php

namespace App\Providers;

use App\Permission;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use function foo\func;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::define('extension',function ($user,$target){
            return Permission::can($user->id,"extension",$target);
        });

        Gate::define('function',function ($user,$extensionName,$function){
            return Permission::can($user->id,"script",strtolower($extensionName) . "_" . $function);
        });

        Gate::define('server',function ($user,$target){
            return Permission::can($user->id,'server',$target);
        });
    }
}
