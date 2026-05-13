<?php

declare(strict_types=1);

namespace App\Enum;

enum ExamScheduleGenerationJobStatus: string
{
    case Queued = 'queued';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
}
