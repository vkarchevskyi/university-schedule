<?php

declare(strict_types=1);

namespace App\Service\TeacherUnavailability;

use App\Entity\TeacherUnavailability;
use App\Service\AbstractEntityService;

final class DeleteTeacherUnavailabilityService extends AbstractEntityService
{
    public function handle(int $id): void
    {
        $this->delete($this->getEntity(TeacherUnavailability::class, $id));
    }
}
