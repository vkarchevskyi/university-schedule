<?php

declare(strict_types=1);

namespace App\Enum;

enum WeekParity: int
{
    case Odd = 1;
    case Even = 2;
    case Both = 3;
}
