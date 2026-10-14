<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;

/**
 * Mencatat login / logout / login gagal ke activity log (log_name = auth).
 * Didaftarkan otomatis oleh event discovery Laravel (method berawalan "handle").
 */
class LogAuthenticationActivity
{
    public function __construct(private Request $request) {}

    public function handleLogin(Login $event): void
    {
        activity('auth')
            ->causedBy($event->user)
            ->event('login')
            ->withProperties($this->context())
            ->log('Login');
    }

    public function handleLogout(Logout $event): void
    {
        if (! $event->user) {
            return;
        }

        activity('auth')
            ->causedBy($event->user)
            ->event('logout')
            ->withProperties($this->context())
            ->log('Logout');
    }

    public function handleFailed(Failed $event): void
    {
        // Jangan pernah simpan password; cukup email yang dicoba.
        activity('auth')
            ->causedBy($event->user) // null kalau email tidak terdaftar
            ->event('login_failed')
            ->withProperties($this->context() + ['email' => $event->credentials['email'] ?? null])
            ->log('Login gagal');
    }

    private function context(): array
    {
        return [
            'ip' => $this->request->ip(),
            'user_agent' => str($this->request->userAgent())->limit(200)->toString(),
        ];
    }
}
