<?php

declare(strict_types=1);

namespace App\Service\TeachingLoad;

use App\Entity\TeachingLoad;
use App\Service\AbstractEntityService;

final class DeleteTeachingLoadService extends AbstractEntityService
{
    public function handle(int $id): void
    {
        $teachingLoad = $this->getEntity(TeachingLoad::class, $id);
        $now = new \DateTimeImmutable();
        $teachingLoad->setDeletedAt($now);
        $teachingLoad->setUpdatedAt($now);
        $this->flush();
    }
}
