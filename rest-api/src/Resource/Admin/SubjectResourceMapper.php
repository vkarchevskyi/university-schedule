<?php

declare(strict_types=1);

namespace App\Resource\Admin;

use App\Entity\Subject;

final readonly class SubjectResourceMapper
{
    public function map(Subject $subject): SubjectResource
    {
        return new SubjectResource($subject->getId(), $subject->getName());
    }
}
