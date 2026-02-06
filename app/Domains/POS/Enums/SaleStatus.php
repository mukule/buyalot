<?php

namespace App\Domains\POS\Enums;

enum SaleStatus: string
{
    case OPEN = 'open';
    case HELD = 'held';
    case COMPLETED = 'completed';
    case VOIDED = 'voided';
}
