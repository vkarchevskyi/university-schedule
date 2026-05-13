<?php

declare(strict_types=1);

namespace App\Resource\Admin;

use App\Entity\AcademicYear;

final readonly class AcademicYearResourceMapper
{
    public function map(AcademicYear $academicYear): AcademicYearResource
    {
        return new AcademicYearResource(
            $academicYear->getId(),
            $academicYear->getName(),
            $academicYear->getStartsAt()->format('Y-m-d'),
            $academicYear->getEndsAt()->format('Y-m-d'),
        );
    }
}
