<?php

declare(strict_types=1);

namespace App\Enum;

enum WeekParity: string
{
    case Odd = 'odd';
    case Even = 'even';
    case Both = 'both';
}
