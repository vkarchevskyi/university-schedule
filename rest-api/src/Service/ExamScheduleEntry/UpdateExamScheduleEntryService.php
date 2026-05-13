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

final readonly class UpdateExamScheduleEntryService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ResolveExamScheduleEntryDataService $resolver,
        private ApplyExamScheduleEntryGroupsService $groups,
        private ValidateExamScheduleRulesService $validator,
        private ExamScheduleEntryResourceMapper $mapper,
    ) {}

    public function handle(int $scheduleId, int $entryId, ExamScheduleEntryRequestDto $request): ExamScheduleEntryResource
    {
        $schedule = $this->entityManager->find(ExamSchedule::class, $scheduleId);
        $entry = $this->entityManager->find(ExamScheduleEntry::class, $entryId);

        if (!$schedule instanceof ExamSchedule || $schedule->getDeletedAt() !== null || !$entry instanceof ExamScheduleEntry || $entry->getDeletedAt() !== null || $entry->getExamSchedule() !== $schedule) {
            throw ApiException::notFound();
        }

        $data = $this->resolver->resolve($request, $entry);
        $entry->setType($data->type);
        $entry->setSubject($data->subject);
        $entry->setTeacher($data->teacher);
        $entry->setRoom($data->room);
        $entry->setEntryDate($data->entryDate);
        $entry->setStartsAt($data->startsAt);
        $this->groups->handle($entry, $data->groups);

        $result = $this->validator->validate($schedule);
        if (!$result->valid) {
            throw ApiException::http(['valid' => false, 'conflicts' => $result->conflicts], 422);
        }

        $this->entityManager->flush();

        return $this->mapper->map($entry);
    }
}
