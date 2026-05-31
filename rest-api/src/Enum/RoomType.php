<?php

declare(strict_types=1);

namespace App\Enum;

enum RoomType: string
{
    case Lecture = 'lecture';
    case Computer = 'computer';
}
