<?php

declare(strict_types=1);

namespace App\Dto\Admin;

use App\Dto\AbstractRequestDto;
use Symfony\Component\Validator\Constraints as Assert;

final class ExamScheduleEntryRequestDto extends AbstractRequestDto
{
    /**
     * @param list<int>|null $groupIds
     */
    public function __construct(
        #[Assert\NotNull(groups: ['create'])]
        #[Assert\Choice(choices: ['consultation', 'exam', 1, 2], groups: ['create', 'update'])]
        public readonly int|string|null $type = null,
        #[Assert\NotNull(groups: ['create'])]
        #[Assert\Type('integer', groups: ['create', 'update'])]
        #[Assert\Positive(groups: ['create', 'update'])]
        public readonly ?int $subjectId = null,
        #[Assert\NotNull(groups: ['create'])]
        #[Assert\Type('integer', groups: ['create', 'update'])]
        #[Assert\Positive(groups: ['create', 'update'])]
        public readonly ?int $teacherId = null,
        #[Assert\NotNull(groups: ['create'])]
        #[Assert\Type('integer', groups: ['create', 'update'])]
        #[Assert\Positive(groups: ['create', 'update'])]
        public readonly ?int $roomId = null,
        #[Assert\NotNull(groups: ['create'])]
        #[Assert\All([new Assert\Type('integer'), new Assert\Positive()], groups: ['create', 'update'])]
        #[Assert\Count(min: 1, groups: ['create', 'update'])]
        public readonly ?array $groupIds = null,
        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Date(groups: ['create', 'update'])]
        public readonly ?string $entryDate = null,
        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Time(groups: ['create', 'update'])]
        public readonly ?string $startsAt = null,
    ) {}
}
