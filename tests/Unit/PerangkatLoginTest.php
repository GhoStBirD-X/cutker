<?php

namespace Tests\Unit;

use App\Enums\PerangkatLogin;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PerangkatLoginTest extends TestCase
{
    /**
     * @return array<string, array{0: string|null, 1: PerangkatLogin}>
     */
    public static function userAgentProvider(): array
    {
        return [
            'android chrome' => ['Mozilla/5.0 (Linux; Android 14; SM-A155F) AppleWebKit/537.36 Chrome/129.0 Mobile Safari/537.36', PerangkatLogin::Android],
            'iphone safari' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148', PerangkatLogin::Ios],
            'ipad lama' => ['Mozilla/5.0 (iPad; CPU OS 12_5 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148', PerangkatLogin::Ios],
            'windows chrome' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/129.0 Safari/537.36', PerangkatLogin::Komputer],
            'tanpa user agent' => [null, PerangkatLogin::Komputer],
        ];
    }

    #[DataProvider('userAgentProvider')]
    public function test_device_is_detected_from_user_agent(?string $userAgent, PerangkatLogin $perangkat): void
    {
        $this->assertSame($perangkat, PerangkatLogin::dariUserAgent($userAgent));
    }
}
