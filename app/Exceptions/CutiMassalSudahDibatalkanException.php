<?php

namespace App\Exceptions;

use RuntimeException;

class CutiMassalSudahDibatalkanException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Cuti massal ini sudah dibatalkan sebelumnya.');
    }
}
