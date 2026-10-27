<?php

namespace App\Enums;

enum PerangkatLogin: string
{
    case Android = 'android';
    case Ios = 'ios';
    case Komputer = 'komputer';

    /**
     * Tebak jenis perangkat dari header User-Agent. iPad dengan iPadOS 13+
     * mengaku sebagai Macintosh, sehingga ikut terhitung Komputer.
     */
    public static function dariUserAgent(?string $userAgent): self
    {
        $userAgent = (string) $userAgent;

        return match (true) {
            str_contains($userAgent, 'Android') => self::Android,
            str_contains($userAgent, 'iPhone'), str_contains($userAgent, 'iPad'), str_contains($userAgent, 'iPod') => self::Ios,
            default => self::Komputer,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Android => 'Android',
            self::Ios => 'iPhone/iPad',
            self::Komputer => 'Komputer/lainnya',
        };
    }
}
