#!/usr/bin/env python3
"""Parse the semester 1 timetable XLS into JSON for Doctrine fixtures."""

from __future__ import annotations

import json
import re
import sys
from collections import Counter, defaultdict
from pathlib import Path

import xlrd

DAY_MAP = {
    'понеділок': 1,
    'вівторок': 2,
    'середа': 3,
    'четвер': 4,
    'пятниця': 5,
    "п'ятниця": 5,
}

LESSON_TYPE_RE = re.compile(r'\(([^)]+)\)\s*$')
SUBGROUP_RE = re.compile(r'\(\s*([IІ]+)\s*підгрупа\s*\)', re.I)
TITLE_RE = re.compile(
    r'^(доц\.?|викл\.?|викл|к\.т\.н\.|к\.е\.н\.|к\.п\.н\.|к\.ф-м\.н\.|ст\.викл\.|проф\.)\s*',
    re.I,
)
SPLIT_LINE_RE = re.compile(r'\s{4,}')

KNOWN_ROOMS = {
    *(f'Ауд. {number}' for number in (1, 2, 3, 4, 5, 6, 8, 9, 10, 11, 13, 14, 15, 16, 17, 20)),
    'Бібліотека',
    'Дистанційно',
    'Конференц-зал',
}

EXTRA_GROUPS = [
    {'name': 'МР-24', 'speciality': 'Маркетинг', 'course': 2, 'studentCount': 25},
    {'name': 'МД-24', 'speciality': 'Менеджмент', 'course': 2, 'studentCount': 25},
    {'name': 'МР-25', 'speciality': 'Маркетинг', 'course': 1, 'studentCount': 25},
    {'name': 'МД-25', 'speciality': 'Менеджмент', 'course': 1, 'studentCount': 25},
]

IMPLICIT_LESSONS = {
    'Базова військова підготовка': ('Не визначено', 'Н.В.', 'practical', 'Дистанційно'),
    'Автоматизованя системи керування підприємством': ('Неділько', 'В.М.', 'practical', None),
    'Екологія': ('Не визначено', 'Н.В.', 'seminar', None),
}


def normalize_initials(value: str) -> str:
    normalized = value.strip().replace(' ', '').upper()
    if normalized and not normalized.endswith('.'):
        normalized += '.'
    return normalized


def normalize_teacher(first_name: str) -> str:
    return normalize_initials(first_name)


def expand_groups(header: str) -> list[str]:
    parts = [part.strip() for part in header.split(',')]
    suffix = next((match.group(0) for part in parts if (match := re.search(r'-\d{2}$', part))), None)
    expanded: list[str] = []

    for part in parts:
        if re.search(r'-\d{2}$', part):
            expanded.append(part)
        elif suffix is not None:
            expanded.append(part + suffix)
        else:
            expanded.append(part)

    return expanded


def parse_lesson_type(marker: str) -> str:
    normalized = marker.lower().replace(' ', '')
    if normalized in ('л', 'лек'):
        return 'lecture'
    if normalized in ('с', 'сем'):
        return 'seminar'
    if 'лб' in normalized or 'лаб' in normalized:
        return 'laboratory'
    if normalized in ('пр', 'п', 'прак') or '/пр' in normalized:
        return 'practical'
    if 'л/с' in normalized or 'с/л' in normalized:
        return 'seminar'
    if 'л/лб' in normalized:
        return 'laboratory'

    return 'practical'


def extract_subgroup(line: str) -> tuple[str, int | None]:
    match = SUBGROUP_RE.search(line)
    if match is None:
        return line, None

    subgroup = len(match.group(1))
    cleaned = (line[: match.start()] + line[match.end():]).strip()
    cleaned = re.sub(r'\s*-\s*$', '', cleaned).strip()

    return cleaned, subgroup


def parse_subject_line(line: str) -> tuple[str, str, int | None]:
    line, subgroup = extract_subgroup(line.strip())
    match = LESSON_TYPE_RE.search(line)
    if match is None:
        return line, 'practical', subgroup

    return line[: match.start()].strip(), parse_lesson_type(match.group(1)), subgroup


def parse_teacher_line(line: str) -> tuple[str | None, str | None, str | None]:
    line = TITLE_RE.sub('', line.strip())
    room: str | None = None

    for token, room_name in (
        ('к/з', 'Конференц-зал'),
        ('бібл', 'Бібліотека'),
        ('дист', 'Дистанційно'),
    ):
        if token in line.lower():
            line = re.split(r'\s{2,}|\s+' + re.escape(token), line, flags=re.I)[0].strip()
            room = room_name
            break

    match = re.search(r'\s+(\d{1,2})(?:/\d+)?\s*$', line)
    if match is not None and room is None:
        room = f'Ауд. {int(match.group(1))}'
        line = line[: match.start()].strip()

    parts = line.split()
    if len(parts) < 2:
        return None, None, room

    return parts[0], ' '.join(parts[1:]), room


def parse_room_line(line: str) -> str | None:
    normalized = line.strip().lower()
    if 'к/з' in normalized:
        return 'Конференц-зал'
    if 'бібл' in normalized:
        return 'Бібліотека'
    if 'дист' in normalized:
        return 'Дистанційно'

    match = re.search(r'(\d{1,2})', normalized)
    if match is None:
        return None

    return f'Ауд. {int(match.group(1))}'


def lesson_from_parts(
    subject: str,
    lesson_type: str,
    teacher_last: str,
    teacher_first: str,
    room: str | None,
    subgroup: int | None = None,
) -> dict[str, object] | None:
    if room is not None and room not in KNOWN_ROOMS:
        return None

    return {
        'subject': subject,
        'lessonType': lesson_type,
        'teacherLastName': teacher_last,
        'teacherFirstName': normalize_teacher(teacher_first),
        'room': room or '',
        'subgroup': subgroup,
    }


def parse_parallel_lessons(lines: list[str]) -> list[dict[str, str]]:
    subject_parts = [part.strip() for part in SPLIT_LINE_RE.split(lines[0]) if part.strip()]
    teacher_parts = [part.strip() for part in SPLIT_LINE_RE.split(lines[1]) if part.strip()]
    room_parts = [part.strip() for part in SPLIT_LINE_RE.split(lines[2]) if part.strip()] if len(lines) >= 3 else []

    lessons: list[dict[str, object]] = []
    for index, subject_line in enumerate(subject_parts):
        subject, lesson_type, subgroup = parse_subject_line(subject_line)
        teacher_line = teacher_parts[index] if index < len(teacher_parts) else ''
        teacher_last, teacher_first, room = parse_teacher_line(teacher_line)
        if room is None and index < len(room_parts):
            room = parse_room_line(room_parts[index])
        if teacher_last is None or teacher_first is None or room is None:
            continue
        lesson = lesson_from_parts(subject, lesson_type, teacher_last, teacher_first, room, subgroup)
        if lesson is not None:
            lessons.append(lesson)

    return lessons


def parse_implicit_lesson(subject_line: str, room_line: str | None) -> dict[str, object] | None:
    subject, lesson_type, subgroup = parse_subject_line(subject_line.strip())
    defaults = IMPLICIT_LESSONS.get(subject)
    if defaults is None:
        return None

    teacher_last, teacher_first, default_lesson_type, default_room = defaults
    room = parse_room_line(room_line) if room_line else None
    room = room or default_room
    if room is None:
        return None

    return lesson_from_parts(subject, lesson_type or default_lesson_type, teacher_last, teacher_first, room, subgroup)


def parse_cell(text: object) -> list[dict[str, object]]:
    if text is None or not str(text).strip():
        return []

    lines = [line.strip() for line in str(text).split('\n') if line.strip()]
    if not lines:
        return []

    if len(lines) == 1:
        lesson = parse_implicit_lesson(lines[0], None)
        return [lesson] if lesson is not None else []

    if SPLIT_LINE_RE.search(lines[0]) and len(lines) >= 2:
        return parse_parallel_lessons(lines)

    subject, lesson_type, subgroup = parse_subject_line(lines[0])

    if len(lines) >= 2 and 'бібл' in lines[1].lower():
        teacher_last, teacher_first, room = parse_teacher_line(lines[1])
        if teacher_last is None:
            lesson = lesson_from_parts(subject, lesson_type, 'Буряк', 'Ю.В.', 'Бібліотека', subgroup)
            return [lesson] if lesson is not None else []

    teacher_last, teacher_first, room = parse_teacher_line(lines[1])
    if room is None and len(lines) >= 3:
        room = parse_room_line(lines[-1])
    if room is None and len(lines) >= 2:
        room = parse_room_line(lines[1])

    if teacher_last is None and teacher_first is None:
        if room is not None and 'фізика' in subject.lower():
            lesson = lesson_from_parts(subject, lesson_type, 'Буряк', 'Ю.В.', room, subgroup)
            return [lesson] if lesson is not None else []
        lesson = parse_implicit_lesson(lines[0], lines[1] if len(lines) >= 2 else None)
        return [lesson] if lesson is not None else []

    if teacher_last is None or teacher_first is None:
        return []

    lesson = lesson_from_parts(subject, lesson_type, teacher_last, teacher_first, room, subgroup)
    return [lesson] if lesson is not None else []


def merge_entries(entries: list[dict[str, object]]) -> list[dict[str, object]]:
    merged: dict[str, dict[str, object]] = {}

    for entry in entries:
        key = '|'.join(
            [
                str(entry['dayOfWeek']),
                str(entry['timeSlotNumber']),
                str(entry['weekParity']),
                str(entry['subject']),
                str(entry['lessonType']),
                str(entry['teacherLastName']),
                normalize_initials(str(entry['teacherFirstName'])),
                str(entry['room']),
                str(entry.get('subgroup')),
            ]
        )
        if key not in merged:
            merged[key] = {**entry, 'groups': list(dict.fromkeys(entry['groups']))}
            continue

        existing_groups = list(merged[key]['groups'])
        for group in entry['groups']:
            if group not in existing_groups:
                existing_groups.append(group)
        merged[key]['groups'] = existing_groups

    return list(merged.values())


def fill_missing_rooms(entries: list[dict[str, object]]) -> None:
    for entry in entries:
        if entry['room']:
            continue

        for other in entries:
            if other is entry:
                continue
            if (
                other['subject'] == entry['subject']
                and other['teacherLastName'] == entry['teacherLastName']
                and other['teacherFirstName'] == entry['teacherFirstName']
                and other['room']
                and set(other['groups']) & set(entry['groups'])
            ):
                entry['room'] = other['room']
                break

        if entry['room']:
            continue

        for other in entries:
            if other is entry:
                continue
            if (
                other['subject'] == entry['subject']
                and other['teacherLastName'] == entry['teacherLastName']
                and other['teacherFirstName'] == entry['teacherFirstName']
                and other['room']
            ):
                entry['room'] = other['room']
                break

        if not entry['room']:
            entry['room'] = 'Дистанційно'


def build_teaching_loads(entries: list[dict[str, object]]) -> list[dict[str, object]]:
    counts: Counter[tuple[str, str, str, str, str, object]] = Counter()

    for entry in entries:
        for group in entry['groups']:
            counts[
                (
                    group,
                    entry['subject'],
                    entry['teacherLastName'],
                    entry['teacherFirstName'],
                    entry['lessonType'],
                    entry.get('subgroup'),
                )
            ] += 1

    loads: list[dict[str, object]] = []
    seen: set[tuple[str, str, str, str, str, object]] = set()

    for (group, subject, last_name, first_name, lesson_type, subgroup), count in sorted(
        counts.items(), key=lambda item: tuple('' if value is None else str(value) for value in item[0])
    ):
        key = (group, subject, last_name, first_name, lesson_type, subgroup)
        if key in seen:
            continue
        seen.add(key)
        loads.append(
            {
                'group': group,
                'subject': subject,
                'teacherLastName': last_name,
                'teacherFirstName': normalize_initials(first_name),
                'lessonType': lesson_type,
                'requiredLessonCount': count,
                'subgroup': subgroup,
            }
        )

    return loads


def build_column_groups(sheet) -> dict[int, list[str]]:
    groups_by_col: dict[int, list[str]] = {}
    column_count = sheet.ncols

    for column in range(column_count):
        header = str(sheet.cell_value(7, column)).strip()
        if header in ('', 'день'):
            continue

        groups_by_col[column] = expand_groups(header)

    for column in range(column_count):
        header = str(sheet.cell_value(7, column)).strip()
        if header not in ('', 'день'):
            continue

        previous_header = str(sheet.cell_value(7, column - 1)).strip() if column > 0 else 'BLOCK'
        if previous_header not in ('', 'день') and (column - 1) in groups_by_col:
            groups_by_col[column] = groups_by_col[column - 1]

    return groups_by_col


def slot_rows_for(sheet, slot_start_row: int) -> list[int]:
    rows = [slot_start_row]

    for row in range(slot_start_row + 1, sheet.nrows):
        day_cell = str(sheet.cell_value(row, 0)).strip().lower()
        if day_cell in DAY_MAP:
            break

        slot_value = sheet.cell_value(row, 1)
        if isinstance(slot_value, (int, float)) and 1 <= slot_value <= 6:
            break

        rows.append(row)

    return rows


def row_has_lesson_content(sheet, row: int, groups_by_col: dict[int, list[str]]) -> bool:
    for column in groups_by_col:
        if parse_cell(sheet.cell_value(row, column)):
            return True

    return False


def week_parity_for_row(sheet, row: int, slot_rows: list[int], groups_by_col: dict[int, list[str]]) -> str:
    content_rows = [slot_row for slot_row in slot_rows if row_has_lesson_content(sheet, slot_row, groups_by_col)]

    if len(content_rows) <= 1:
        return 'both'

    try:
        index = content_rows.index(row)
    except ValueError:
        return 'both'

    return 'odd' if index % 2 == 0 else 'even'


def cell_has_lesson_content(sheet, row: int, column: int) -> bool:
    return bool(parse_cell(sheet.cell_value(row, column)))


def apply_row_subgroup(
    sheet,
    row: int,
    column: int,
    lesson: dict[str, object],
    groups_by_col: dict[int, list[str]],
) -> None:
    if lesson.get('subgroup') is not None:
        return

    header = str(sheet.cell_value(7, column)).strip()
    if header in ('', 'день'):
        if column - 1 in groups_by_col and cell_has_lesson_content(sheet, row, column - 1):
            lesson['subgroup'] = 2
        return

    next_column = column + 1
    next_header = str(sheet.cell_value(7, next_column)).strip() if next_column < sheet.ncols else 'BLOCK'
    if next_header in ('',) and cell_has_lesson_content(sheet, row, next_column):
        lesson['subgroup'] = 1


def parse_workbook(path: Path) -> dict[str, object]:
    workbook = xlrd.open_workbook(path)
    sheet = workbook.sheet_by_name('мал')
    groups_by_col = build_column_groups(sheet)

    entries: list[dict[str, object]] = []
    current_day: int | None = None
    current_slot: int | None = None
    slot_rows: list[int] = []

    for row in range(8, sheet.nrows):
        day_cell = str(sheet.cell_value(row, 0)).strip().lower()
        if day_cell in DAY_MAP:
            current_day = DAY_MAP[day_cell]

        slot_value = sheet.cell_value(row, 1)
        if isinstance(slot_value, (int, float)) and 1 <= slot_value <= 6:
            current_slot = int(slot_value)
            slot_rows = slot_rows_for(sheet, row)

        if current_day is None or current_slot is None or not slot_rows:
            continue

        week_parity = week_parity_for_row(sheet, row, slot_rows, groups_by_col)
        row_entries: list[dict[str, object]] = []
        for column, groups in groups_by_col.items():
            for lesson in parse_cell(sheet.cell_value(row, column)):
                apply_row_subgroup(sheet, row, column, lesson, groups_by_col)
                row_entries.append(
                    {
                        'groups': groups,
                        'dayOfWeek': current_day,
                        'timeSlotNumber': current_slot,
                        'weekParity': week_parity,
                        **lesson,
                    }
                )

        entries.extend(row_entries)

    fill_missing_rooms(entries)
    entries = merge_entries(entries)

    return {
        'groups': EXTRA_GROUPS,
        'teachingLoads': build_teaching_loads(entries),
        'entries': entries,
    }


def main() -> int:
    root = Path(__file__).resolve().parents[1]
    default_source = root / 'Розклад  І семестр 25-26.xls'
    source = Path(sys.argv[1]) if len(sys.argv) > 1 else default_source
    target = root / 'rest-api' / 'src' / 'DataFixtures' / 'data' / 'semester1_timetable.json'

    if not source.exists():
        print(f'Source file not found: {source}', file=sys.stderr)
        return 1

    payload = parse_workbook(source)
    target.parent.mkdir(parents=True, exist_ok=True)
    target.write_text(
        json.dumps(payload, ensure_ascii=False, indent=2) + '\n',
        encoding='utf-8',
    )
    print(
        f'Wrote {len(payload["entries"])} entries, '
        f'{len(payload["teachingLoads"])} teaching loads to {target}'
    )

    return 0


if __name__ == '__main__':
    raise SystemExit(main())
