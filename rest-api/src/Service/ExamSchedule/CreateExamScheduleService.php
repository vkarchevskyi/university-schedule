<?php

declare(strict_types=1);

namespace App\Service\ExamSchedule;

use App\Dto\Admin\ExamScheduleRequestDto;
use App\Entity\Admin;
use App\Entity\ExamSchedule;
use App\Entity\Semester;
use App\Enum\ExamScheduleStatus;
use App\Exception\ApiException;
use App\Resource\Admin\ExamScheduleResource;
use App\Resource\Admin\ExamScheduleResourceMapper;
use App\Service\AbstractEntityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class CreateExamScheduleService extends AbstractEntityService
{
    public function __construct(
        private readonly ExamScheduleResourceMapper $mapper,
        private readonly Security $security,
        EntityManagerInterface $entityManager,
    ) {
        parent::__construct($entityManager);
    }

    public function handle(ExamScheduleRequestDto $request): ExamScheduleResource
    {
        $schedule = new ExamSchedule(
            $this->getEntity(Semester::class, $this->positiveInt($request->semesterId)),
            ExamScheduleStatus::Draft,
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
}
