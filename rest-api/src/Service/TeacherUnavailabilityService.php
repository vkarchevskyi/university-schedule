<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Admin\TeacherUnavailabilityRequestDto;
use App\Entity\Teacher;
use App\Entity\TeacherUnavailability;
use App\Exception\ApiException;

final class TeacherUnavailabilityService extends AbstractEntityService
{
    /** @return array{items: list<array<string, mixed>>} */
    public function list(): array
    {
        return ['items' => array_map($this->serialize(...), $this->listEntities(TeacherUnavailability::class))];
    }

    /** @return array<string, mixed> */
    public function get(int $id): array
    {
        return $this->serialize($this->getEntity(TeacherUnavailability::class, $id));
    }

    /** @return array<string, mixed> */
    public function create(TeacherUnavailabilityRequestDto $data): array
    {
        $unavailability = new TeacherUnavailability(
            $this->getEntity(Teacher::class, $this->positiveInt($data->teacherId)),
            $this->dayOfWeek($data->dayOfWeek),
            $this->time($data->unavailableFrom),
            $this->time($data->unavailableTo),
        );
        $this->validateRange($unavailability);
        $this->save($unavailability);

        return $this->serialize($unavailability);
    }

    /** @return array<string, mixed> */
    public function update(int $id, TeacherUnavailabilityRequestDto $data): array
    {
        $unavailability = $this->getEntity(TeacherUnavailability::class, $id);

        if ($data->has('teacherId')) {
            $unavailability->setTeacher($this->getEntity(Teacher::class, $this->positiveInt($data->teacherId)));
        }
        if ($data->has('dayOfWeek')) {
            $unavailability->setDayOfWeek($this->dayOfWeek($data->dayOfWeek));
        }
        if ($data->has('unavailableFrom')) {
            $unavailability->setUnavailableFrom($this->time($data->unavailableFrom));
        }
        if ($data->has('unavailableTo')) {
            $unavailability->setUnavailableTo($this->time($data->unavailableTo));
        }

        $this->validateRange($unavailability);
        $this->flush();

        return $this->serialize($unavailability);
    }

    public function deleteById(int $id): void
    {
        $this->delete($this->getEntity(TeacherUnavailability::class, $id));
    }

    private function validateRange(TeacherUnavailability $unavailability): void
    {
        if ($unavailability->getUnavailableFrom() >= $unavailability->getUnavailableTo()) {
            throw ApiException::validation(['unavailableTo' => 'End time must be after start time.']);
        }
    }

    /** @return array<string, mixed> */
    private function serialize(TeacherUnavailability $unavailability): array
    {
        return ['id' => $unavailability->getId(), 'teacherId' => $unavailability->getTeacher()->getId(), 'dayOfWeek' => $unavailability->getDayOfWeek(), 'unavailableFrom' => $unavailability->getUnavailableFrom()->format('H:i'), 'unavailableTo' => $unavailability->getUnavailableTo()->format('H:i')];
    }
}
