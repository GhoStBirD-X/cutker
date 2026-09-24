<?php

namespace Tests\Unit;

use App\Support\SaldoSeverity;
use PHPUnit\Framework\TestCase;

class SaldoSeverityTest extends TestCase
{
    public function test_null_sisa_or_kuota_is_unlimited(): void
    {
        $this->assertSame(SaldoSeverity::UNLIMITED, SaldoSeverity::hitung(null, 12));
        $this->assertSame(SaldoSeverity::UNLIMITED, SaldoSeverity::hitung(5, null));
    }

    public function test_negative_sisa_is_critical(): void
    {
        $this->assertSame(SaldoSeverity::CRITICAL, SaldoSeverity::hitung(-1, 12));
    }

    public function test_sisa_at_or_below_25_percent_of_kuota_is_warning(): void
    {
        $this->assertSame(SaldoSeverity::WARNING, SaldoSeverity::hitung(3, 12));
        $this->assertSame(SaldoSeverity::WARNING, SaldoSeverity::hitung(0, 12));
    }

    public function test_sisa_above_25_percent_of_kuota_is_safe(): void
    {
        $this->assertSame(SaldoSeverity::SAFE, SaldoSeverity::hitung(4, 12));
        $this->assertSame(SaldoSeverity::SAFE, SaldoSeverity::hitung(12, 12));
    }

    public function test_zero_kuota_with_positive_sisa_is_safe(): void
    {
        $this->assertSame(SaldoSeverity::SAFE, SaldoSeverity::hitung(5, 0));
    }

    public function test_zero_kuota_with_zero_sisa_is_warning(): void
    {
        $this->assertSame(SaldoSeverity::WARNING, SaldoSeverity::hitung(0, 0));
    }

    public function test_warna_teks_and_badge_are_defined_for_every_severity(): void
    {
        foreach ([SaldoSeverity::UNLIMITED, SaldoSeverity::SAFE, SaldoSeverity::WARNING, SaldoSeverity::CRITICAL] as $severity) {
            $this->assertMatchesRegularExpression('/^[0-9A-F]{6}$/', SaldoSeverity::warnaTeks($severity));

            $badge = SaldoSeverity::warnaBadge($severity);
            $this->assertMatchesRegularExpression('/^[0-9A-F]{6}$/', $badge['fill']);
            $this->assertMatchesRegularExpression('/^[0-9A-F]{6}$/', $badge['teks']);
        }
    }
}
