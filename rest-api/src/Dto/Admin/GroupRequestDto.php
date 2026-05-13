<?php

declare(strict_types=1);

namespace App\Dto\Admin;

use App\Dto\AbstractRequestDto;
use Symfony\Component\Validator\Constraints as Assert;

final class GroupRequestDto extends AbstractRequestDto
{
    public function __construct(
        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Type('string', groups: ['create', 'update'])]
        public readonly ?string $name = null,
        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Type('string', groups: ['create', 'update'])]
        public readonly ?string $speciality = null,
        #[Assert\NotNull(groups: ['create'])]
        #[Assert\Type('integer', groups: ['create', 'update'])]
        #[Assert\Positive(groups: ['create', 'update'])]
        public readonly ?int $course = null,
        #[Assert\NotNull(groups: ['create'])]
        #[Assert\Type('integer', groups: ['create', 'update'])]
        #[Assert\PositiveOrZero(groups: ['create', 'update'])]
        public readonly ?int $studentCount = null,
    ) {}
}
