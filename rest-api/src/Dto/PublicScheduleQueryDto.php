<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class PublicScheduleQueryDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Choice(choices: ['group', 'teacher', 'room'])]
        public readonly ?string $type = null,
        #[Assert\NotNull]
        #[Assert\Type('integer')]
        #[Assert\Positive]
        public readonly ?int $id = null,
        #[Assert\NotBlank]
        #[Assert\Date]
        public readonly ?string $weekStart = null,
    ) {}

    #[Assert\Callback]
    public function validateWeekStart(ExecutionContextInterface $context): void
    {
        if ($this->weekStart === null || $this->weekStart === '') {
            return;
        }

        $weekStart = \DateTimeImmutable::createFromFormat('!Y-m-d', $this->weekStart);

        if (!$weekStart instanceof \DateTimeImmutable) {
            return;
        }

        if ($weekStart->format('N') !== '1') {
            $context->buildViolation('Week start must be Monday.')
                ->atPath('weekStart')
                ->addViolation();
        }
    }

    public function weekStartDate(): \DateTimeImmutable
    {
        return new \DateTimeImmutable($this->weekStart ?? 'now');
    }
}
