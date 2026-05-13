<?php

declare(strict_types=1);

namespace App\Service\TimeSlot;

use App\Entity\TimeSlot;
use App\Service\AbstractEntityService;

final class DeleteTimeSlotService extends AbstractEntityService
{
    public function handle(int $id): void
    {
        $this->delete($this->getEntity(TimeSlot::class, $id));
    }
}
