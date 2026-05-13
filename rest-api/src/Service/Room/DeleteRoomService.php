<?php

declare(strict_types=1);

namespace App\Service\Room;

use App\Entity\Room;
use App\Service\AbstractEntityService;

final class DeleteRoomService extends AbstractEntityService
{
    public function handle(int $id): void
    {
        $this->delete($this->getEntity(Room::class, $id));
    }
}
