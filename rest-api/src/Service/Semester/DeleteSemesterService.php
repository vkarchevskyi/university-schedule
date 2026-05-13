<?php

declare(strict_types=1);

namespace App\Service\Semester;

use App\Entity\Semester;
use App\Service\AbstractEntityService;

final class DeleteSemesterService extends AbstractEntityService
{
    public function handle(int $id): void
    {
        $this->delete($this->getEntity(Semester::class, $id));
    }
}
