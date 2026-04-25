<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SubjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubjectRepository::class)]
#[ORM\Table(name: 'subjects')]
class Subject
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING)]
    private string $name;

    /** @var Collection<int, TeacherSubject> */
    #[ORM\OneToMany(targetEntity: TeacherSubject::class, mappedBy: 'subject', cascade: ['persist', 'remove'])]
    private Collection $teacherSubjects;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->teacherSubjects = new ArrayCollection();
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

    /** @return Collection<int, TeacherSubject> */
    public function getTeacherSubjects(): Collection
    {
        return $this->teacherSubjects;
    }
}
