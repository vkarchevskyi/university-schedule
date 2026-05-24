<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Group as StudentGroup;
use App\Entity\Room;
use App\Entity\Teacher;
use App\Exception\ApiException;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ListTelegramTargetsService
{
    public const PAGE_SIZE = 8;

    public function __construct(private EntityManagerInterface $entityManager) {}

    public function list(string $type, int $page): TelegramTargetPage
    {
        $page = max(0, $page);
        $items = $this->items($type);
        $offset = $page * self::PAGE_SIZE;

        return new TelegramTargetPage(
            array_slice($items, $offset, self::PAGE_SIZE),
            $page,
            $page > 0,
            $offset + self::PAGE_SIZE < count($items),
        );
    }

    /** @return list<TelegramTargetListItem> */
    private function items(string $type): array
    {
        return match ($type) {
            'group' => $this->groups(),
            'teacher' => $this->teachers(),
            'room' => $this->rooms(),
            default => throw ApiException::validation(['type' => 'Підтримуються лише group, teacher або room.']),
        };
    }

    /** @return list<TelegramTargetListItem> */
    private function groups(): array
    {
        $groups = $this->entityManager->getRepository(StudentGroup::class)->findBy([], ['name' => 'ASC', 'id' => 'ASC']);

        return array_values(array_filter(array_map(
            fn(StudentGroup $group): ?TelegramTargetListItem => $group->getId() === null ? null : new TelegramTargetListItem('group', $group->getId(), $group->getName()),
            $groups,
        )));
    }

    /** @return list<TelegramTargetListItem> */
    private function teachers(): array
    {
        $teachers = $this->entityManager->getRepository(Teacher::class)->findBy([], ['lastName' => 'ASC', 'firstName' => 'ASC', 'id' => 'ASC']);

        return array_values(array_filter(array_map(
            fn(Teacher $teacher): ?TelegramTargetListItem => $teacher->getId() === null
                ? null
                : new TelegramTargetListItem('teacher', $teacher->getId(), sprintf('%s %s', $teacher->getFirstName(), $teacher->getLastName())),
            $teachers,
        )));
    }

    /** @return list<TelegramTargetListItem> */
    private function rooms(): array
    {
        $rooms = $this->entityManager->getRepository(Room::class)->findBy([], ['name' => 'ASC', 'id' => 'ASC']);

        return array_values(array_filter(array_map(
            fn(Room $room): ?TelegramTargetListItem => $room->getId() === null ? null : new TelegramTargetListItem('room', $room->getId(), $room->getName()),
            $rooms,
        )));
    }
}
