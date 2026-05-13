<?php

declare(strict_types=1);

namespace App\Service\TeacherUnavailability;

use App\Dto\Admin\TeacherUnavailabilityRequestDto;
use App\Entity\Teacher;
use App\Entity\TeacherUnavailability;
use App\Exception\ApiException;
use App\Resource\Admin\TeacherUnavailabilityResource;
use App\Resource\Admin\TeacherUnavailabilityResourceMapper;
use App\Service\AbstractEntityService;
use Doctrine\ORM\EntityManagerInterface;

final class UpdateTeacherUnavailabilityService extends AbstractEntityService
{
    public function __construct(private readonly TeacherUnavailabilityResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function handle(int $id, TeacherUnavailabilityRequestDto $data): TeacherUnavailabilityResource
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

        return $this->mapper->map($unavailability);
    }

    private function validateRange(TeacherUnavailability $unavailability): void
    {
        if ($unavailability->getUnavailableFrom() >= $unavailability->getUnavailableTo()) {
            throw ApiException::validation(['unavailableTo' => 'End time must be after start time.']);
        }
    }
}
