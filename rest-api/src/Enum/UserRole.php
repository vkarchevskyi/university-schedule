<?php

declare(strict_types=1);

namespace App\Enum;

enum UserRole: string
{
    case User = 'user';
    case Admin = 'admin';
}
