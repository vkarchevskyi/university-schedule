<?php

declare(strict_types=1);

namespace App\Dto\Admin;

use App\Dto\AbstractRequestDto;
use Symfony\Component\Validator\Constraints as Assert;

final class AcademicYearRequestDto extends AbstractRequestDto
{
    public function __construct(
        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Type('string', groups: ['create', 'update'])]
        public readonly ?string $name = null,
        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Date(groups: ['create', 'update'])]
        public readonly ?string $startsAt = null,
        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Date(groups: ['create', 'update'])]
        public readonly ?string $endsAt = null,
    ) {}
}
