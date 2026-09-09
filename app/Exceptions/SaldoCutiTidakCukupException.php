<?php

namespace App\Exceptions;

use RuntimeException;

class SaldoCutiTidakCukupException extends RuntimeException
{
    public function __construct(int $sisa, int $diminta)
    {
        parent::__construct("Sisa saldo cuti tidak mencukupi. Sisa: {$sisa} hari, diajukan: {$diminta} hari.");
    }
}
