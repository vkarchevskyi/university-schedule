<?php

declare(strict_types=1);

namespace App\Service\Teacher;

use App\Entity\Teacher;
use App\Service\AbstractEntityService;

final class DeleteTeacherService extends AbstractEntityService
{
    public function handle(int $id): void
    {
        $this->delete($this->getEntity(Teacher::class, $id));
    }
}
