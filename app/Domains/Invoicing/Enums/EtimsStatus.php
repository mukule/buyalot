<?php

namespace App\Domains\Invoicing\Enums;

enum EtimsStatus: string
{
    case PENDING = 'pending';
    case SIGNED = 'signed';
    case FAILED = 'failed';
}
