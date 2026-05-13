<?php

declare(strict_types=1);

namespace App\Dto\Admin;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final readonly class ScheduleRequestDto
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\Type('integer')]
        #[Assert\Positive]
        public ?int $semesterId = null,
        #[Assert\NotBlank]
        #[Assert\Date]
        public ?string $validFrom = null,
        #[Assert\NotBlank]
        #[Assert\Date]
        public ?string $validTo = null,
    ) {}

    #[Assert\Callback]
    public function validateDateRange(ExecutionContextInterface $context): void
    {
        if ($this->validFrom === null || $this->validTo === null) {
            return;
        }

        $validFrom = \DateTimeImmutable::createFromFormat('!Y-m-d', $this->validFrom);
        $validTo = \DateTimeImmutable::createFromFormat('!Y-m-d', $this->validTo);

        if (!$validFrom instanceof \DateTimeImmutable || !$validTo instanceof \DateTimeImmutable) {
            return;
        }

        if ($validTo < $validFrom) {
            $context->buildViolation('Schedule end date must not be before start date.')
                ->atPath('validTo')
                ->addViolation();
        }
    }
}
