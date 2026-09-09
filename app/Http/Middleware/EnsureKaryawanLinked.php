<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class EnsureKaryawanLinked
{
    /**
     * Beberapa halaman (cuti, approval) mengandalkan data karyawan milik
     * user yang login. Jika akun belum dihubungkan ke karyawan (mis. baru
     * dibuat admin lewat Kelola User tapi belum di-link), tolak dengan toast
     * yang jelas alih-alih membiarkan controller error saat memakai data
     * karyawan yang null.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->karyawan) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Akun Anda belum terhubung ke data karyawan. Hubungi admin untuk menghubungkannya lewat menu Kelola User.',
            ]);

            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
