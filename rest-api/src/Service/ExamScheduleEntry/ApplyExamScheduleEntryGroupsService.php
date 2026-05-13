<?php

declare(strict_types=1);

namespace App\Service\ExamScheduleEntry;

use App\Entity\ExamScheduleEntry;
use App\Entity\ExamScheduleEntryGroup;
use App\Entity\Group;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ApplyExamScheduleEntryGroupsService
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    /**
     * @param list<Group> $groups
     */
    public function handle(ExamScheduleEntry $entry, array $groups): void
    {
        $requestedGroupIds = [];
        foreach ($groups as $group) {
            $id = $group->getId();
            if ($id !== null) {
                $requestedGroupIds[] = $id;
            }
        }

        $existingGroupIds = [];
        foreach ($entry->getGroups() as $entryGroup) {
            $id = $entryGroup->getGroup()->getId();
            if ($id !== null) {
                $existingGroupIds[] = $id;
            }
        }

        $existingById = [];
        foreach ($entry->getGroups() as $entryGroup) {
            $id = $entryGroup->getGroup()->getId();
            if ($id !== null) {
                $existingById[$id] = $entryGroup;
            }
        }

        foreach ($existingById as $groupId => $entryGroup) {
            if (!in_array($groupId, $requestedGroupIds, true)) {
                $entry->removeGroup($entryGroup);
                $this->entityManager->remove($entryGroup);
            }
        }

        foreach ($groups as $group) {
            $groupId = $group->getId();
            if ($groupId !== null && in_array($groupId, $existingGroupIds, true)) {
                continue;
            }

            $entryGroup = new ExamScheduleEntryGroup($entry, $group);
            $entry->addGroup($entryGroup);
        }
    }
}
