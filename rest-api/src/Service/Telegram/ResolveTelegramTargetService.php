<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Group as StudentGroup;
use App\Entity\Room;
use App\Entity\Teacher;
use App\Exception\ApiException;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ResolveTelegramTargetService
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    public function get(string $type, string $name): TelegramTarget
    {
        return match ($this->normalize($type)) {
            'group' => $this->group($name),
            'teacher' => $this->teacher($name),
            'room' => $this->room($name),
            default => throw ApiException::validation(['type' => 'Підтримуються лише group, teacher або room.']),
        };
    }

    private function group(string $name): TelegramTarget
    {
        $group = $this->entityManager->getRepository(StudentGroup::class)->createQueryBuilder('g')
            ->andWhere('g.name = :rawName OR LOWER(g.name) = :name')
            ->setParameter('rawName', trim($name))
            ->setParameter('name', $this->normalize($name))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$group instanceof StudentGroup || $group->getId() === null) {
            throw ApiException::validation(['target' => 'Групу не знайдено.']);
        }

        return new TelegramTarget('group', $group->getId(), $group->getName());
    }

    private function teacher(string $name): TelegramTarget
    {
        $needle = $this->normalize($name);

        foreach ($this->entityManager->getRepository(Teacher::class)->findAll() as $teacher) {
            if ($teacher->getId() === null) {
                continue;
            }

            $firstLast = $this->normalize(sprintf('%s %s', $teacher->getFirstName(), $teacher->getLastName()));
            $lastFirst = $this->normalize(sprintf('%s %s', $teacher->getLastName(), $teacher->getFirstName()));

            if ($needle === $firstLast || $needle === $lastFirst) {
                return new TelegramTarget('teacher', $teacher->getId(), sprintf('%s %s', $teacher->getFirstName(), $teacher->getLastName()));
            }
        }

        throw ApiException::validation(['target' => 'Викладача не знайдено.']);
    }

    private function room(string $name): TelegramTarget
    {
        $room = $this->entityManager->getRepository(Room::class)->createQueryBuilder('r')
            ->andWhere('r.name = :rawName OR LOWER(r.name) = :name')
            ->setParameter('rawName', trim($name))
            ->setParameter('name', $this->normalize($name))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$room instanceof Room || $room->getId() === null) {
            throw ApiException::validation(['target' => 'Аудиторію не знайдено.']);
        }

        return new TelegramTarget('room', $room->getId(), $room->getName());
    }

    private function normalize(string $value): string
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $value) ?? $value);

        return function_exists('mb_strtolower') ? mb_strtolower($normalized) : strtolower($normalized);
    }
}
