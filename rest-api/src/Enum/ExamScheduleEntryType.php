<?php

declare(strict_types=1);

namespace App\Enum;

enum ExamScheduleEntryType: int
{
    case Consultation = 1;
    case Exam = 2;
}
