<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;

abstract class AbstractRequestDto
{
    public function has(string $field): bool
    {
        return property_exists($this, $field) && $this->{$field} !== null;
    }

    #[Assert\Callback(groups: ['update'])]
    public function validateUpdatePayload(ExecutionContextInterface $context): void
    {
        foreach (get_object_vars($this) as $value) {
            if ($value !== null) {
                return;
            }
        }

        $context->buildViolation('Expected at least one field to update.')
            ->atPath('json')
            ->addViolation();
    }
}
