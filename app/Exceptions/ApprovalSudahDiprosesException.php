<?php

namespace App\Exceptions;

use App\Enums\StatusApproval;
use RuntimeException;

class ApprovalSudahDiprosesException extends RuntimeException
{
    public function __construct(?StatusApproval $status = null)
    {
        parent::__construct($status === StatusApproval::Dibatalkan
            ? 'Pengajuan ini sudah dibatalkan oleh pengaju.'
            : 'Pengajuan ini sudah diproses oleh approver lain.');
    }
}
