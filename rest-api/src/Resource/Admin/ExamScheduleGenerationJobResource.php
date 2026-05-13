<?php

declare(strict_types=1);

namespace App\Resource\Admin;

final readonly class ExamScheduleGenerationJobResource
{
    /** @param array<string, mixed>|null $diagnostics */
    public function __construct(
        public string $id,
        public int $semesterId,
        public int $requestedBy,
        public string $status,
        public ?int $generatedExamScheduleId,
        public ?int $qualityScore,
        public ?string $qualityStatus,
        public ?string $errorMessage,
        public ?array $diagnostics,
        public string $createdAt,
        public ?string $startedAt,
        public ?string $finishedAt,
    ) {}
}
