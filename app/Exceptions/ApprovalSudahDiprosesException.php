<?php

namespace App\Exceptions;

use RuntimeException;

class ApprovalSudahDiprosesException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Pengajuan ini sudah diproses oleh approver lain.');
    }
}
