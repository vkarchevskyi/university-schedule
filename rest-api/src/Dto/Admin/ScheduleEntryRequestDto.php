<?php

declare(strict_types=1);

namespace App\Dto\Admin;

use App\Dto\AbstractRequestDto;
use Symfony\Component\Validator\Constraints as Assert;

final class ScheduleEntryRequestDto extends AbstractRequestDto
{
    /**
     * @param list<int>|null $teachingLoadIds
     * @param list<int>|null $groupIds
     */
    public function __construct(
        #[Assert\NotNull(groups: ['create'])]
        #[Assert\Type('array', groups: ['create', 'update'])]
        #[Assert\Count(min: 1, groups: ['create', 'update'])]
        #[Assert\All([
            new Assert\Type('integer'),
            new Assert\Positive(),
        ], groups: ['create', 'update'])]
        public readonly ?array $teachingLoadIds = null,
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
        public readonly ?int $roomId = null,
        #[Assert\NotNull(groups: ['create'])]
        #[Assert\Type('integer', groups: ['create', 'update'])]
        #[Assert\Positive(groups: ['create', 'update'])]
        public readonly ?int $timeSlotId = null,
        #[Assert\NotNull(groups: ['create'])]
        #[Assert\Type('integer', groups: ['create', 'update'])]
        #[Assert\Range(min: 1, max: 7, groups: ['create', 'update'])]
        public readonly ?int $dayOfWeek = null,
        #[Assert\NotNull(groups: ['create'])]
        #[Assert\Choice(choices: ['odd', 'even', 'both', 1, 2, 3], groups: ['create', 'update'])]
        public readonly int|string|null $weekParity = null,
        #[Assert\NotNull(groups: ['create'])]
        #[Assert\Type('array', groups: ['create', 'update'])]
        #[Assert\Count(min: 1, groups: ['create', 'update'])]
        #[Assert\All([
            new Assert\Type('integer'),
            new Assert\Positive(),
        ], groups: ['create', 'update'])]
        public readonly ?array $groupIds = null,
    ) {}
}
