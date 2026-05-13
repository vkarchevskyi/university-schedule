<?php

declare(strict_types=1);

namespace App\Service\AcademicYear;

use App\Entity\AcademicYear;
use App\Service\AbstractEntityService;

final class DeleteAcademicYearService extends AbstractEntityService
{
    public function handle(int $id): void
    {
        $this->delete($this->getEntity(AcademicYear::class, $id));
    }
}
