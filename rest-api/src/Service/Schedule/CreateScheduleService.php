<?php

declare(strict_types=1);

namespace App\Service\Schedule;

use App\Dto\Admin\ScheduleRequestDto;
use App\Entity\Admin;
use App\Entity\Schedule;
use App\Entity\Semester;
use App\Enum\ScheduleStatus;
use App\Exception\ApiException;
use App\Resource\Admin\ScheduleResource;
use App\Resource\Admin\ScheduleResourceMapper;
use App\Service\AbstractEntityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class CreateScheduleService extends AbstractEntityService
{
    public function __construct(
        private readonly ScheduleResourceMapper $mapper,
        private readonly Security $security,
        EntityManagerInterface $entityManager,
    ) {
        parent::__construct($entityManager);
    }

    public function handle(ScheduleRequestDto $data): ScheduleResource
    {
        $schedule = new Schedule(
            $this->getEntity(Semester::class, $this->positiveInt($data->semesterId)),
            ScheduleStatus::Draft,
            $this->scheduleDate($data->validFrom),
            $this->scheduleDate($data->validTo),
            $this->currentAdmin(),
            new \DateTimeImmutable(),
        );
        $this->save($schedule);

        return $this->mapper->map($schedule);
    }

    private function currentAdmin(): Admin
    {
        $user = $this->security->getUser();

        if (!$user instanceof Admin) {
            throw ApiException::http(['error' => 'Authenticated admin was not found.'], 401);
        }

        return $user;
    }

    private function scheduleDate(?string $value): \DateTimeImmutable
    {
        if ($value === null) {
            throw ApiException::validation(['date' => 'Expected valid date.']);
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        if (!$date instanceof \DateTimeImmutable) {
            throw ApiException::validation(['date' => 'Expected valid date.']);
        }

        return $date;
    }
}
