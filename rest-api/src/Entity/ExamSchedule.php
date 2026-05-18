<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ExamScheduleStatus;
use App\Repository\ExamScheduleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExamScheduleRepository::class)]
#[ORM\Table(name: 'exam_schedules')]
class ExamSchedule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Semester::class, inversedBy: 'examSchedules')]
    #[ORM\JoinColumn(name: 'semester_id', referencedColumnName: 'id', nullable: false)]
    private ?Semester $semester;

    #[ORM\Column(type: Types::SMALLINT, enumType: ExamScheduleStatus::class)]
    private ExamScheduleStatus $status;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'examSchedules')]
    #[ORM\JoinColumn(name: 'created_by', referencedColumnName: 'id', nullable: false)]
    private User $createdBy;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'published_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $publishedAt;

    #[ORM\Column(name: 'deleted_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $deletedAt;

    /** @var Collection<int, ExamScheduleEntry> */
    #[ORM\OneToMany(targetEntity: ExamScheduleEntry::class, mappedBy: 'examSchedule', cascade: ['persist', 'remove'])]
    private Collection $entries;

    public function __construct(
        Semester $semester,
        ExamScheduleStatus $status,
        User $createdBy,
        \DateTimeImmutable $createdAt,
        ?\DateTimeImmutable $publishedAt = null,
        ?\DateTimeImmutable $deletedAt = null,
    ) {
        $this->semester = $semester;
        $this->status = $status;
        $this->createdBy = $createdBy;
        $this->createdAt = $createdAt;
        $this->publishedAt = $publishedAt;
        $this->deletedAt = $deletedAt;
        $this->entries = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSemester(): ?Semester
    {
        return $this->semester;
    }

    public function setSemester(?Semester $semester): void
    {
        $this->semester = $semester;
    }

    public function getStatus(): ExamScheduleStatus
    {
        return $this->status;
    }

    public function setStatus(ExamScheduleStatus $status): void
    {
        $this->status = $status;
    }

    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTimeImmutable $publishedAt): void
    {
        $this->publishedAt = $publishedAt;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): void
    {
        $this->deletedAt = $deletedAt;
    }

    /** @return Collection<int, ExamScheduleEntry> */
    public function getEntries(): Collection
    {
        return $this->entries;
    }

    public function addEntry(ExamScheduleEntry $entry): void
    {
        if (!$this->entries->contains($entry)) {
            $this->entries->add($entry);
            $entry->setExamSchedule($this);
        }
    }
}
