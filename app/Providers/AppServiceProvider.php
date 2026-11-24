<?php

namespace App\Providers;

use App\Models\LogAktivitas;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Jejak audit akses: login, logout, dan percobaan login gagal (tanpa menyimpan kata sandi).
        Event::listen(Login::class, fn (Login $e) => LogAktivitas::catatPeristiwa('login', $e->user->getAuthIdentifier(), $e->user->username ?? null));
        Event::listen(Logout::class, fn (Logout $e) => $e->user ? LogAktivitas::catatPeristiwa('logout', $e->user->getAuthIdentifier(), $e->user->username ?? null) : null);
        Event::listen(Failed::class, fn (Failed $e) => LogAktivitas::catatPeristiwa(
            'login_gagal',
            $e->user?->getAuthIdentifier(),
            mb_substr((string) ($e->credentials['username'] ?? ''), 0, 100) ?: null,
        ));
    }
}
