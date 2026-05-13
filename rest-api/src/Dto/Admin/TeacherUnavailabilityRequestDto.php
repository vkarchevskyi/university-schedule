<?php

declare(strict_types=1);

namespace App\Dto\Admin;

use App\Dto\AbstractRequestDto;
use Symfony\Component\Validator\Constraints as Assert;

final class TeacherUnavailabilityRequestDto extends AbstractRequestDto
{
    public function __construct(
        #[Assert\NotNull(groups: ['create'])]
        #[Assert\Type('integer', groups: ['create', 'update'])]
        #[Assert\Positive(groups: ['create', 'update'])]
        public readonly ?int $teacherId = null,
        #[Assert\NotNull(groups: ['create'])]
        #[Assert\Type('integer', groups: ['create', 'update'])]
        #[Assert\Range(min: 1, max: 7, groups: ['create', 'update'])]
        public readonly ?int $dayOfWeek = null,
        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Time(groups: ['create', 'update'])]
        public readonly ?string $unavailableFrom = null,
        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Time(groups: ['create', 'update'])]
        public readonly ?string $unavailableTo = null,
    ) {}
}
