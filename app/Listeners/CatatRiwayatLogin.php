<?php

namespace App\Listeners;

use App\Enums\PerangkatLogin;
use App\Models\RiwayatLogin;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;

/**
 * Catat jenis perangkat tiap login (termasuk login otomatis "ingat saya")
 * untuk melihat sebaran Android/iPhone/komputer, lihat laporan:perangkat-login.
 */
class CatatRiwayatLogin
{
    public function __construct(protected Request $request) {}

    public function handle(Login $event): void
    {
        $userAgent = $this->request->userAgent();

        RiwayatLogin::create([
            'user_id' => $event->user->getAuthIdentifier(),
            'perangkat' => PerangkatLogin::dariUserAgent($userAgent),
            'user_agent' => $userAgent,
            'ip_address' => $this->request->ip(),
        ]);
    }
}
