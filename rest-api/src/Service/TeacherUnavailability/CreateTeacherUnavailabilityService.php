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

final class CreateTeacherUnavailabilityService extends AbstractEntityService
{
    public function __construct(private readonly TeacherUnavailabilityResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function handle(TeacherUnavailabilityRequestDto $data): TeacherUnavailabilityResource
    {
        $unavailability = new TeacherUnavailability(
            $this->getEntity(Teacher::class, $this->positiveInt($data->teacherId)),
            $this->dayOfWeek($data->dayOfWeek),
            $this->time($data->unavailableFrom),
            $this->time($data->unavailableTo),
        );
        $this->validateRange($unavailability);
        $this->save($unavailability);

        return $this->mapper->map($unavailability);
    }

    private function validateRange(TeacherUnavailability $unavailability): void
    {
        if ($unavailability->getUnavailableFrom() >= $unavailability->getUnavailableTo()) {
            throw ApiException::validation(['unavailableTo' => 'End time must be after start time.']);
        }
    }
}
