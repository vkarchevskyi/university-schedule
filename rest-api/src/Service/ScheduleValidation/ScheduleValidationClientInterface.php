<?php

declare(strict_types=1);

namespace App\Service\ScheduleValidation;

use App\Resource\Admin\ScheduleValidationResource;

interface ScheduleValidationClientInterface
{
    /** @param array<string, mixed> $payload */
    public function validate(array $payload): ScheduleValidationResource;
}
