<?php

declare(strict_types=1);

namespace App\Enum;

enum ExamScheduleStatus: int
{
    case Draft = 1;
    case Published = 2;
}
