<?php

declare(strict_types=1);

namespace App\Dto\Admin;

use App\Dto\AbstractRequestDto;
use Symfony\Component\Validator\Constraints as Assert;

final class TeachingLoadRequestDto extends AbstractRequestDto
{
    public function __construct(
        #[Assert\NotNull(groups: ['create'])]
        #[Assert\Type('integer', groups: ['create', 'update'])]
        #[Assert\Positive(groups: ['create', 'update'])]
        public readonly ?int $semesterId = null,
        #[Assert\NotNull(groups: ['create'])]
        #[Assert\Type('integer', groups: ['create', 'update'])]
        #[Assert\Positive(groups: ['create', 'update'])]
        public readonly ?int $groupId = null,
        #[Assert\NotNull(groups: ['create'])]
        #[Assert\Type('integer', groups: ['create', 'update'])]
        #[Assert\Positive(groups: ['create', 'update'])]
        public readonly ?int $subjectId = null,
        #[Assert\NotNull(groups: ['create'])]
        #[Assert\Type('integer', groups: ['create', 'update'])]
        #[Assert\Positive(groups: ['create', 'update'])]
        public readonly ?int $teacherId = null,
        #[Assert\NotNull(groups: ['create'])]
        #[Assert\Choice(choices: ['lecture', 'laboratory', 'lab', 'seminar', 'practical', 1, 2, 3, 4], groups: ['create', 'update'])]
        public readonly int|string|null $lessonType = null,
        #[Assert\NotNull(groups: ['create'])]
        #[Assert\Type('integer', groups: ['create', 'update'])]
        #[Assert\Positive(groups: ['create', 'update'])]
        public readonly ?int $requiredLessonCount = null,
        #[Assert\Type('bool', groups: ['create', 'update'])]
        public readonly ?bool $requiresComputerRoom = null,
    ) {}
}
