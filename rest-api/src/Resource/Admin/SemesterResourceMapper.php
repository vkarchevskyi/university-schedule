<?php

declare(strict_types=1);

namespace App\Resource\Admin;

use App\Entity\Semester;

final readonly class SemesterResourceMapper
{
    public function map(Semester $semester): SemesterResource
    {
        return new SemesterResource(
            $semester->getId(),
            $semester->getAcademicYear()?->getId(),
            $semester->getNumber(),
            $semester->getStartsAt()->format('Y-m-d'),
            $semester->getEndsAt()->format('Y-m-d'),
            strtolower($semester->getFirstWeekParity()->name),
        );
    }
}
