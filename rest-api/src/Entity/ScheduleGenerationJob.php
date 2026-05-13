<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ScheduleGenerationJobStatus;
use App\Repository\ScheduleGenerationJobRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ScheduleGenerationJobRepository::class)]
#[ORM\Table(name: 'schedule_generation_jobs')]
class ScheduleGenerationJob
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Semester::class)]
    #[ORM\JoinColumn(name: 'semester_id', referencedColumnName: 'id', nullable: false)]
    private Semester $semester;

    #[ORM\ManyToOne(targetEntity: Admin::class)]
    #[ORM\JoinColumn(name: 'requested_by', referencedColumnName: 'id', nullable: false)]
    private Admin $requestedBy;

    #[ORM\Column(type: Types::STRING, enumType: ScheduleGenerationJobStatus::class)]
    private ScheduleGenerationJobStatus $status;

    #[ORM\ManyToOne(targetEntity: Schedule::class)]
    #[ORM\JoinColumn(name: 'generated_schedule_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Schedule $generatedSchedule = null;

    #[ORM\Column(name: 'quality_score', type: Types::INTEGER, nullable: true)]
    private ?int $qualityScore = null;

    #[ORM\Column(name: 'quality_status', type: Types::STRING, length: 32, nullable: true)]
    private ?string $qualityStatus = null;

    #[ORM\Column(name: 'error_message', type: Types::TEXT, nullable: true)]
    private ?string $errorMessage = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $diagnostics = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'started_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(name: 'finished_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $finishedAt = null;

    public function __construct(string $id, Semester $semester, Admin $requestedBy, \DateTimeImmutable $createdAt)
    {
        $this->id = $id;
        $this->semester = $semester;
        $this->requestedBy = $requestedBy;
        $this->status = ScheduleGenerationJobStatus::Queued;
        $this->createdAt = $createdAt;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSemester(): Semester
    {
        return $this->semester;
    }

    public function getRequestedBy(): Admin
    {
        return $this->requestedBy;
    }

    public function getStatus(): ScheduleGenerationJobStatus
    {
        return $this->status;
    }

    public function getGeneratedSchedule(): ?Schedule
    {
        return $this->generatedSchedule;
    }

    public function getQualityScore(): ?int
    {
        return $this->qualityScore;
    }

    public function getQualityStatus(): ?string
    {
        return $this->qualityStatus;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    /** @return array<string, mixed>|null */
    public function getDiagnostics(): ?array
    {
        return $this->diagnostics;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getFinishedAt(): ?\DateTimeImmutable
    {
        return $this->finishedAt;
    }
}
