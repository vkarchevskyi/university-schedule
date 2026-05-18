<?php

declare(strict_types=1);

namespace App\Resource\Admin;

final readonly class CurrentAdminResource
{
    public function __construct(public AdminProfileResource $admin) {}
}
