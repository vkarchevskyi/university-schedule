<?php

declare(strict_types=1);

namespace App\DataFixtures;

final class SemesterOneTimetableData
{
    private const DATA_FILE = __DIR__ . '/data/semester1_timetable.json';

    /**
     * @return array{
     *     groups: list<array{name: string, speciality: string, course: int, studentCount: int}>,
     *     teachingLoads: list<array{
     *         group: string,
     *         subject: string,
     *         teacherLastName: string,
     *         teacherFirstName: string,
     *         lessonType: string,
     *         requiredLessonCount: int,
     *         subgroup: int|null
     *     }>,
     *     entries: list<array{
     *         groups: list<string>,
     *         dayOfWeek: int,
     *         timeSlotNumber: int,
     *         weekParity: string,
     *         subject: string,
     *         lessonType: string,
     *         teacherLastName: string,
     *         teacherFirstName: string,
     *         room: string,
     *         subgroup: int|null
     *     }>
     * }
     */
    public static function load(): array
    {
        /** @var array<string, mixed> $data */
        $data = json_decode((string) file_get_contents(self::DATA_FILE), true, flags: JSON_THROW_ON_ERROR);

        return $data;
    }

    public static function teacherKey(string $lastName, string $firstName): string
    {
        return $lastName . '|' . self::normalizeInitials($firstName);
    }

    public static function normalizeInitials(string $value): string
    {
        $normalized = mb_strtoupper(str_replace(' ', '', trim($value)));

        if ($normalized !== '' && !str_ends_with($normalized, '.')) {
            $normalized .= '.';
        }

        return $normalized;
    }

    /**
     * @param list<array<string, mixed>> $entries
     *
     * @return list<array<string, mixed>>
     */
    public static function mergeEntries(array $entries): array
    {
        /** @var array<string, array<string, mixed>> $merged */
        $merged = [];

        foreach ($entries as $entry) {
            $key = implode('|', [
                (string) $entry['dayOfWeek'],
                (string) $entry['timeSlotNumber'],
                (string) $entry['weekParity'],
                (string) $entry['subject'],
                (string) $entry['lessonType'],
                (string) $entry['teacherLastName'],
                self::normalizeInitials((string) $entry['teacherFirstName']),
                (string) $entry['room'],
                (string) ($entry['subgroup'] ?? ''),
            ]);

            if (!isset($merged[$key])) {
                $entry['groups'] = array_values(array_unique($entry['groups']));
                $merged[$key] = $entry;
                continue;
            }

            $merged[$key]['groups'] = array_values(array_unique([
                ...$merged[$key]['groups'],
                ...$entry['groups'],
            ]));
        }

        return array_values($merged);
    }
}
