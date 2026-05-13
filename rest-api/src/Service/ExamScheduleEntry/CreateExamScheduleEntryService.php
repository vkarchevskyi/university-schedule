<?php

declare(strict_types=1);

namespace App\Service\ExamScheduleEntry;

use App\Dto\Admin\ExamScheduleEntryRequestDto;
use App\Entity\ExamSchedule;
use App\Entity\ExamScheduleEntry;
use App\Exception\ApiException;
use App\Resource\Admin\ExamScheduleEntryResource;
use App\Resource\Admin\ExamScheduleEntryResourceMapper;
use App\Service\ExamScheduleValidation\ValidateExamScheduleRulesService;
use Doctrine\ORM\EntityManagerInterface;

final readonly class CreateExamScheduleEntryService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ResolveExamScheduleEntryDataService $resolver,
        private ApplyExamScheduleEntryGroupsService $groups,
        private ValidateExamScheduleRulesService $validator,
        private ExamScheduleEntryResourceMapper $mapper,
    ) {}

    public function handle(int $scheduleId, ExamScheduleEntryRequestDto $request): ExamScheduleEntryResource
    {
        $schedule = $this->entityManager->find(ExamSchedule::class, $scheduleId);

        if (!$schedule instanceof ExamSchedule || $schedule->getDeletedAt() !== null) {
            throw ApiException::notFound();
        }

        $data = $this->resolver->resolve($request);
        $entry = new ExamScheduleEntry($schedule, $data->type, $data->subject, $data->teacher, $data->room, $data->entryDate, $data->startsAt);
        $schedule->addEntry($entry);
        $this->groups->handle($entry, $data->groups);

        $result = $this->validator->validate($schedule);
        if (!$result->valid) {
            throw ApiException::http(['valid' => false, 'conflicts' => $result->conflicts], 422);
        }

        $this->entityManager->persist($entry);
        $this->entityManager->flush();

        return $this->mapper->map($entry);
    }
}
