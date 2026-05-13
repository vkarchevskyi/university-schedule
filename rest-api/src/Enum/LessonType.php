<?php

declare(strict_types=1);

namespace App\Enum;

enum LessonType: int
{
    case Lecture = 1;
    case Laboratory = 2;
    case Seminar = 3;
    case Practical = 4;
}
