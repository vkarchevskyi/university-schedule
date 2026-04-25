<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ScheduleStatus;
use App\Repository\ScheduleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ScheduleRepository::class)]
#[ORM\Table(name: 'schedules')]
class Schedule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Semester::class, inversedBy: 'schedules')]
    #[ORM\JoinColumn(name: 'semester_id', referencedColumnName: 'id', nullable: false)]
    private ?Semester $semester;

    #[ORM\Column(type: Types::STRING, enumType: ScheduleStatus::class)]
    private ScheduleStatus $status;

    #[ORM\Column(name: 'valid_from', type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $validFrom;

    #[ORM\Column(name: 'valid_to', type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $validTo;

    #[ORM\ManyToOne(targetEntity: Admin::class, inversedBy: 'schedules')]
    #[ORM\JoinColumn(name: 'created_by', referencedColumnName: 'id', nullable: false)]
    private Admin $createdBy;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'published_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $publishedAt;

    /** @var Collection<int, ScheduleEntry> */
    #[ORM\OneToMany(targetEntity: ScheduleEntry::class, mappedBy: 'schedule', cascade: ['persist', 'remove'])]
    private Collection $entries;

    public function __construct(
        Semester $semester,
        ScheduleStatus $status,
        \DateTimeImmutable $validFrom,
        \DateTimeImmutable $validTo,
        Admin $createdBy,
        \DateTimeImmutable $createdAt,
        ?\DateTimeImmutable $publishedAt = null,
    ) {
        $this->semester = $semester;
        $this->status = $status;
        $this->validFrom = $validFrom;
        $this->validTo = $validTo;
        $this->createdBy = $createdBy;
        $this->createdAt = $createdAt;
        $this->publishedAt = $publishedAt;
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

    public function getStatus(): ScheduleStatus
    {
        return $this->status;
    }

    public function setStatus(ScheduleStatus $status): void
    {
        $this->status = $status;
    }

    public function getValidFrom(): \DateTimeImmutable
    {
        return $this->validFrom;
    }

    public function setValidFrom(\DateTimeImmutable $validFrom): void
    {
        $this->validFrom = $validFrom;
    }

    public function getValidTo(): \DateTimeImmutable
    {
        return $this->validTo;
    }

    public function setValidTo(\DateTimeImmutable $validTo): void
    {
        $this->validTo = $validTo;
    }

    public function getCreatedBy(): Admin
    {
        return $this->createdBy;
    }

    public function setCreatedBy(Admin $createdBy): void
    {
        $this->createdBy = $createdBy;
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

    /** @return Collection<int, ScheduleEntry> */
    public function getEntries(): Collection
    {
        return $this->entries;
    }

    public function addEntry(ScheduleEntry $entry): void
    {
        if (!$this->entries->contains($entry)) {
            $this->entries->add($entry);
            $entry->setSchedule($this);
        }
    }

    public function removeEntry(ScheduleEntry $entry): void
    {
        if ($this->entries->removeElement($entry)) {
            if ($entry->getSchedule() === $this) {
                $entry->setSchedule(null);
            }
        }
    }
}
