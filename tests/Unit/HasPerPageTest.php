<?php

namespace Tests\Unit;

use App\Http\Controllers\Concerns\HasPerPage;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class HasPerPageTest extends TestCase
{
    private function resolver(): object
    {
        return new class
        {
            use HasPerPage;

            public function resolve(Request $request, int $default = 10): int
            {
                return $this->resolvePerPage($request, $default);
            }
        };
    }

    public function test_returns_default_when_per_page_is_not_given(): void
    {
        $result = $this->resolver()->resolve(Request::create('/'));

        $this->assertSame(10, $result);
    }

    public function test_returns_custom_default_when_given(): void
    {
        $result = $this->resolver()->resolve(Request::create('/'), 30);

        $this->assertSame(30, $result);
    }

    public function test_returns_requested_value_when_it_is_an_allowed_option(): void
    {
        $result = $this->resolver()->resolve(Request::create('/?per_page=20'));

        $this->assertSame(20, $result);
    }

    public function test_falls_back_to_default_when_value_is_not_an_allowed_option(): void
    {
        $result = $this->resolver()->resolve(Request::create('/?per_page=999999'));

        $this->assertSame(10, $result);
    }
}
