<?php

declare(strict_types=1);

namespace App\Service\Group;

use App\Entity\Group as StudentGroup;
use App\Service\AbstractEntityService;

final class DeleteGroupService extends AbstractEntityService
{
    public function handle(int $id): void
    {
        $this->delete($this->getEntity(StudentGroup::class, $id));
    }
}
