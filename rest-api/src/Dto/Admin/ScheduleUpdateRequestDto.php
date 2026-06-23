<?php

declare(strict_types=1);

namespace App\Dto\Admin;

use App\Dto\AbstractRequestDto;
use Symfony\Component\Validator\Constraints as Assert;

final class ScheduleUpdateRequestDto extends AbstractRequestDto
{
    public function __construct(
        #[Assert\NotBlank(groups: ['update'])]
        #[Assert\Date(groups: ['update'])]
        public readonly ?string $validFrom = null,
    ) {}
}
