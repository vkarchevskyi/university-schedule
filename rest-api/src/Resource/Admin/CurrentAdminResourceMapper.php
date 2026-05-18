<?php

declare(strict_types=1);

namespace App\Resource\Admin;

use App\Entity\Admin;

final readonly class CurrentAdminResourceMapper
{
    public function map(Admin $admin): CurrentAdminResource
    {
        $adminId = $admin->getId();
        if ($adminId === null) {
            throw new \LogicException('Expected persisted admin.');
        }

        return new CurrentAdminResource(new AdminProfileResource(
            $adminId,
            $admin->getFirstName(),
            $admin->getLastName(),
            $admin->getEmail(),
        ));
    }
}
