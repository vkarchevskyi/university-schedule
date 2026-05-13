<?php

declare(strict_types=1);

namespace App\Dto\Admin;

use App\Dto\AbstractRequestDto;
use Symfony\Component\Validator\Constraints as Assert;

final class TimeSlotRequestDto extends AbstractRequestDto
{
    public function __construct(
        #[Assert\NotNull(groups: ['create'])]
        #[Assert\Type('integer', groups: ['create', 'update'])]
        #[Assert\Positive(groups: ['create', 'update'])]
        public readonly ?int $number = null,
        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Time(groups: ['create', 'update'])]
        public readonly ?string $startsAt = null,
        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Time(groups: ['create', 'update'])]
        public readonly ?string $endsAt = null,
    ) {}
}
