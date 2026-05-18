<?php

declare(strict_types=1);

namespace App\Resource\Admin;

use App\Entity\Admin;

final readonly class CurrentAdminResourceMapper
{
    public function map(Admin $admin): CurrentAdminResource
    {
        return new CurrentAdminResource(new AdminProfileResource(
            $admin->getId(),
            $admin->getFirstName(),
            $admin->getLastName(),
            $admin->getEmail(),
        ));
    }
}
