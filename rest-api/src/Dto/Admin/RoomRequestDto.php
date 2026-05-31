<?php

declare(strict_types=1);

namespace App\Dto\Admin;

use App\Dto\AbstractRequestDto;
use Symfony\Component\Validator\Constraints as Assert;

final class RoomRequestDto extends AbstractRequestDto
{
    public function __construct(
        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Type('string', groups: ['create', 'update'])]
        public readonly ?string $name = null,
        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Type('string', groups: ['create', 'update'])]
        #[Assert\Choice(choices: ['lecture', 'computer'], groups: ['create', 'update'])]
        public readonly ?string $type = null,
        #[Assert\NotNull(groups: ['create'])]
        #[Assert\Type('integer', groups: ['create', 'update'])]
        #[Assert\Positive(groups: ['create', 'update'])]
        public readonly ?int $capacity = null,
    ) {}
}
