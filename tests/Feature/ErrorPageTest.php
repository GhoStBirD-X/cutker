<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_page_renders_indonesian_error_page_with_status(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/halaman-yang-tidak-ada');

        $response->assertNotFound();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('error')
            ->where('status', 404)
            ->has('auth.user'));
    }

    public function test_expired_session_redirects_back_with_toast_instead_of_error_page(): void
    {
        Route::middleware('web')->post('/_uji/sesi-kedaluwarsa', fn () => throw new TokenMismatchException);

        $response = $this->from('/cuti/ajukan')->post('/_uji/sesi-kedaluwarsa');

        $response->assertRedirect('/cuti/ajukan');
        $response->assertInertiaFlash('toast.message', 'Sesi halaman sudah kedaluwarsa. Silakan coba lagi.');
    }
}
