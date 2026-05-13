<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\LessonType;
use App\Enum\WeekParity;
use App\Exception\ApiException;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractEntityService
{
    public function __construct(protected readonly EntityManagerInterface $entityManager) {}

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return list<T>
     */
    protected function listEntities(string $class): array
    {
        return $this->entityManager->getRepository($class)->findBy([], ['id' => 'ASC']);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    protected function getEntity(string $class, int $id): object
    {
        $entity = $this->entityManager->find($class, $id);

        if (!$entity instanceof $class) {
            throw ApiException::notFound();
        }

        return $entity;
    }

    protected function save(object $entity): void
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    protected function flush(): void
    {
        $this->entityManager->flush();
    }

    protected function delete(object $entity): void
    {
        try {
            $this->entityManager->remove($entity);
            $this->entityManager->flush();
        } catch (ForeignKeyConstraintViolationException) {
            throw ApiException::conflict();
        }
    }

    protected function string(mixed $value): string
    {
        if (!is_string($value)) {
            throw ApiException::validation(['value' => 'Expected string.']);
        }

        $value = trim($value);

        if ($value === '') {
            throw ApiException::validation(['value' => 'Expected non-empty string.']);
        }

        return $value;
    }

    protected function positiveInt(mixed $value): int
    {
        if (!is_int($value)) {
            throw ApiException::validation(['value' => 'Expected integer.']);
        }

        if ($value < 1) {
            throw ApiException::validation(['value' => 'Expected positive integer.']);
        }

        return $value;
    }

    protected function nonNegativeInt(mixed $value): int
    {
        if (!is_int($value)) {
            throw ApiException::validation(['value' => 'Expected integer.']);
        }

        if ($value < 0) {
            throw ApiException::validation(['value' => 'Expected non-negative integer.']);
        }

        return $value;
    }

    protected function dayOfWeek(mixed $value): int
    {
        if (!is_int($value)) {
            throw ApiException::validation(['dayOfWeek' => 'Expected integer.']);
        }

        if ($value < 1 || $value > 7) {
            throw ApiException::validation(['dayOfWeek' => 'Expected day of week from 1 to 7.']);
        }

        return $value;
    }

    protected function date(mixed $value): \DateTimeImmutable
    {
        if (!is_string($value)) {
            throw ApiException::validation(['date' => 'Expected valid date.']);
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            throw ApiException::validation(['date' => 'Expected valid date.']);
        }
    }

    protected function time(mixed $value): \DateTimeImmutable
    {
        if (!is_string($value)) {
            throw ApiException::validation(['time' => 'Expected valid time.']);
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            throw ApiException::validation(['time' => 'Expected valid time.']);
        }
    }

    protected function lessonType(mixed $value): LessonType
    {
        if (is_string($value)) {
            return match (strtolower($value)) {
                'lecture' => LessonType::Lecture,
                'laboratory', 'lab' => LessonType::Laboratory,
                'seminar' => LessonType::Seminar,
                'practical' => LessonType::Practical,
                default => throw ApiException::validation(['lessonType' => 'Unknown lesson type.']),
            };
        }

        if (is_int($value)) {
            try {
                return LessonType::from($value);
            } catch (\ValueError) {
                throw ApiException::validation(['lessonType' => 'Unknown lesson type.']);
            }
        }

        throw ApiException::validation(['lessonType' => 'Unknown lesson type.']);
    }

    protected function weekParity(mixed $value): WeekParity
    {
        if (is_string($value)) {
            return match (strtolower($value)) {
                'odd' => WeekParity::Odd,
                'even' => WeekParity::Even,
                'both' => WeekParity::Both,
                default => throw ApiException::validation(['firstWeekParity' => 'Unknown week parity.']),
            };
        }

        if (is_int($value)) {
            try {
                return WeekParity::from($value);
            } catch (\ValueError) {
                throw ApiException::validation(['firstWeekParity' => 'Unknown week parity.']);
            }
        }

        throw ApiException::validation(['firstWeekParity' => 'Unknown week parity.']);
    }
}
