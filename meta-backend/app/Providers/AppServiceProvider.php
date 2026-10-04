<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Knuckles\Camel\Extraction\ExtractedEndpointData;
use Knuckles\Scribe\Scribe;
use Symfony\Component\HttpFoundation\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local')) {
            if (class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
                $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
                $this->app->register(TelescopeServiceProvider::class);
            }
        }

        if (class_exists(\Knuckles\Scribe\Scribe::class)) {
            Scribe::beforeResponseCall(function (Request $request, ExtractedEndpointData $endpointData) {
                // Scribe runs many Kernel::handle() calls in one process; Sanctum may reuse the
                // previous guard user and skip the new Bearer token → wrong role / 403.
                Auth::forgetGuards();

                $path = ltrim((string) $endpointData->uri, '/');
                $role = match (true) {
                    str_starts_with($path, 'api/v1/student/') => 'student',
                    str_starts_with($path, 'api/v1/admin/') => 'admin',
                    str_starts_with($path, 'api/v1/mentor/') => 'mentor',
                    default => null,
                };

                if ($role === null) {
                    return;
                }

                $user = User::where('email', "{$role}@i.ru")->first();

                if (! $user) {
                    $user = User::factory()->withRole($role)->create();
                }

                $token = $user->createToken('scribe-documentation')->plainTextToken;

                $request->headers->set('Authorization', "Bearer {$token}");
                $request->server->set('HTTP_AUTHORIZATION', "Bearer {$token}");
            });
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });
    }
}
