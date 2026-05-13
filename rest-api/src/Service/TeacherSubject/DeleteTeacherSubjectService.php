<?php

declare(strict_types=1);

namespace App\Service\TeacherSubject;

use App\Entity\TeacherSubject;
use App\Service\AbstractEntityService;

final class DeleteTeacherSubjectService extends AbstractEntityService
{
    public function handle(int $id): void
    {
        $this->delete($this->getEntity(TeacherSubject::class, $id));
    }
}
