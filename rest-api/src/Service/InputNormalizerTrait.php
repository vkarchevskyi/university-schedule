<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\LessonType;
use App\Enum\WeekParity;
use App\Exception\ApiException;

trait InputNormalizerTrait
{
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

        if ($value < 1 || $value > 5) {
            throw ApiException::validation(['dayOfWeek' => 'Expected weekday from 1 to 5.']);
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
