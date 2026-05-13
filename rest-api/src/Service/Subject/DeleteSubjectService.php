<?php

declare(strict_types=1);

namespace App\Service\Subject;

use App\Entity\Subject;
use App\Service\AbstractEntityService;

final class DeleteSubjectService extends AbstractEntityService
{
    public function handle(int $id): void
    {
        $this->delete($this->getEntity(Subject::class, $id));
    }
}
