<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AcademicYearRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AcademicYearRepository::class)]
#[ORM\Table(name: 'academic_years')]
class AcademicYear
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING)]
    private string $name;

    #[ORM\Column(name: 'starts_at', type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $startsAt;

    #[ORM\Column(name: 'ends_at', type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $endsAt;

    /** @var Collection<int, Semester> */
    #[ORM\OneToMany(targetEntity: Semester::class, mappedBy: 'academicYear', cascade: ['persist', 'remove'])]
    private Collection $semesters;

    public function __construct(
        string $name,
        \DateTimeImmutable $startsAt,
        \DateTimeImmutable $endsAt,
    ) {
        $this->name = $name;
        $this->startsAt = $startsAt;
        $this->endsAt = $endsAt;
        $this->semesters = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getStartsAt(): \DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function setStartsAt(\DateTimeImmutable $startsAt): void
    {
        $this->startsAt = $startsAt;
    }

    public function getEndsAt(): \DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function setEndsAt(\DateTimeImmutable $endsAt): void
    {
        $this->endsAt = $endsAt;
    }

    /** @return Collection<int, Semester> */
    public function getSemesters(): Collection
    {
        return $this->semesters;
    }

    public function addSemester(Semester $semester): void
    {
        if (!$this->semesters->contains($semester)) {
            $this->semesters->add($semester);
            $semester->setAcademicYear($this);
        }
    }

    public function removeSemester(Semester $semester): void
    {
        if ($this->semesters->removeElement($semester)) {
            if ($semester->getAcademicYear() === $this) {
                $semester->setAcademicYear(null);
            }
        }
    }
}
