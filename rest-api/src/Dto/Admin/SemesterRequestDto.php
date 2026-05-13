<?php

declare(strict_types=1);

namespace App\Dto\Admin;

use App\Dto\AbstractRequestDto;
use Symfony\Component\Validator\Constraints as Assert;

final class SemesterRequestDto extends AbstractRequestDto
{
    public function __construct(
        #[Assert\NotNull(groups: ['create'])]
        #[Assert\Type('integer', groups: ['create', 'update'])]
        #[Assert\Positive(groups: ['create', 'update'])]
        public readonly ?int $academicYearId = null,
        #[Assert\NotNull(groups: ['create'])]
        #[Assert\Type('integer', groups: ['create', 'update'])]
        #[Assert\Positive(groups: ['create', 'update'])]
        public readonly ?int $number = null,
        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Date(groups: ['create', 'update'])]
        public readonly ?string $startsAt = null,
        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Date(groups: ['create', 'update'])]
        public readonly ?string $endsAt = null,
        #[Assert\NotNull(groups: ['create'])]
        #[Assert\Choice(choices: ['odd', 'even', 'both', 1, 2, 3], groups: ['create', 'update'])]
        public readonly int|string|null $firstWeekParity = null,
    ) {}
}
