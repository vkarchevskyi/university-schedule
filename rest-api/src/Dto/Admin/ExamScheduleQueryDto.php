<?php

declare(strict_types=1);

namespace App\Dto\Admin;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ExamScheduleQueryDto
{
    public function __construct(
        #[Assert\Type('integer')]
        #[Assert\Positive]
        public ?int $semesterId = null,
    ) {}
}
