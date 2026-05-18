<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ExamScheduleGenerationJobStatus;
use App\Repository\ExamScheduleGenerationJobRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExamScheduleGenerationJobRepository::class)]
#[ORM\Table(name: 'exam_schedule_generation_jobs')]
class ExamScheduleGenerationJob
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Semester::class)]
    #[ORM\JoinColumn(name: 'semester_id', referencedColumnName: 'id', nullable: false)]
    private Semester $semester;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'requested_by', referencedColumnName: 'id', nullable: false)]
    private User $requestedBy;

    #[ORM\Column(type: Types::STRING, enumType: ExamScheduleGenerationJobStatus::class)]
    private ExamScheduleGenerationJobStatus $status;

    #[ORM\ManyToOne(targetEntity: ExamSchedule::class)]
    #[ORM\JoinColumn(name: 'generated_exam_schedule_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?ExamSchedule $generatedExamSchedule = null;

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

    public function __construct(string $id, Semester $semester, User $requestedBy, \DateTimeImmutable $createdAt)
    {
        $this->id = $id;
        $this->semester = $semester;
        $this->requestedBy = $requestedBy;
        $this->status = ExamScheduleGenerationJobStatus::Queued;
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

    public function getRequestedBy(): User
    {
        return $this->requestedBy;
    }

    public function getStatus(): ExamScheduleGenerationJobStatus
    {
        return $this->status;
    }

    public function getGeneratedExamSchedule(): ?ExamSchedule
    {
        return $this->generatedExamSchedule;
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
