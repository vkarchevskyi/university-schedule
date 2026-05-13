<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Admin\TimeSlotRequestDto;
use App\Exception\ApiException;
use App\Entity\TimeSlot;

final class TimeSlotService extends AbstractEntityService
{
    /** @return array{items: list<array<string, mixed>>} */
    public function list(): array
    {
        return ['items' => array_map($this->serialize(...), $this->listEntities(TimeSlot::class))];
    }

    /** @return array<string, mixed> */
    public function get(int $id): array
    {
        return $this->serialize($this->getEntity(TimeSlot::class, $id));
    }

    /** @return array<string, mixed> */
    public function create(TimeSlotRequestDto $data): array
    {
        $timeSlot = new TimeSlot($this->positiveInt($data->number), $this->time($data->startsAt), $this->time($data->endsAt));
        $this->validateRange($timeSlot);
        $this->save($timeSlot);

        return $this->serialize($timeSlot);
    }

    /** @return array<string, mixed> */
    public function update(int $id, TimeSlotRequestDto $data): array
    {
        $timeSlot = $this->getEntity(TimeSlot::class, $id);

        if ($data->has('number')) {
            $timeSlot->setNumber($this->positiveInt($data->number));
        }
        if ($data->has('startsAt')) {
            $timeSlot->setStartsAt($this->time($data->startsAt));
        }
        if ($data->has('endsAt')) {
            $timeSlot->setEndsAt($this->time($data->endsAt));
        }

        $this->validateRange($timeSlot);
        $this->flush();

        return $this->serialize($timeSlot);
    }

    public function deleteById(int $id): void
    {
        $this->delete($this->getEntity(TimeSlot::class, $id));
    }

    private function validateRange(TimeSlot $timeSlot): void
    {
        if ($timeSlot->getStartsAt() >= $timeSlot->getEndsAt()) {
            throw ApiException::validation(['endsAt' => 'End time must be after start time.']);
        }
    }

    /** @return array<string, mixed> */
    private function serialize(TimeSlot $timeSlot): array
    {
        return ['id' => $timeSlot->getId(), 'number' => $timeSlot->getNumber(), 'startsAt' => $timeSlot->getStartsAt()->format('H:i'), 'endsAt' => $timeSlot->getEndsAt()->format('H:i')];
    }
}
