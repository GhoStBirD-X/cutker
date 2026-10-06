<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Inertia\ExceptionResponse;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureErrorPages();
    }

    /**
     * Galat 403/404 (dan 500/503 saat debug mati) dirender sebagai halaman
     * Inertia berbahasa Indonesia yang konsisten dengan UI aplikasi,
     * menggantikan halaman galat bawaan Laravel berbahasa Inggris. Sesi/CSRF
     * kedaluwarsa (419) setelah lama mengisi form dikembalikan ke halaman
     * sebelumnya dengan toast, bukan halaman galat.
     */
    protected function configureErrorPages(): void
    {
        Inertia::handleExceptionsUsing(function (ExceptionResponse $response) {
            $status = $response->statusCode();

            if ($status === 419 && ! $response->request->expectsJson()) {
                Inertia::flash('toast', ['type' => 'error', 'message' => 'Sesi halaman sudah kedaluwarsa. Silakan coba lagi.']);

                return back();
            }
            $tampilkanHalamanKustom = in_array($status, [403, 404], true)
                || (in_array($status, [500, 503], true) && ! config('app.debug'));

            if (! $tampilkanHalamanKustom || $response->request->expectsJson()) {
                return null;
            }

            return $response->render('error', ['status' => $status])->withSharedData();
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        // Admin punya akses penuh ke seluruh permission tanpa perlu dicek satu per satu.
        Gate::before(fn ($user) => $user->hasRole('admin') ? true : null);

        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
