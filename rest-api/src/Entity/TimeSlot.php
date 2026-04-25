<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TimeSlotRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TimeSlotRepository::class)]
#[ORM\Table(name: 'time_slots')]
class TimeSlot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $number;

    #[ORM\Column(name: 'starts_at', type: Types::TIME_IMMUTABLE)]
    private \DateTimeImmutable $startsAt;

    #[ORM\Column(name: 'ends_at', type: Types::TIME_IMMUTABLE)]
    private \DateTimeImmutable $endsAt;

    public function __construct(
        int $number,
        \DateTimeImmutable $startsAt,
        \DateTimeImmutable $endsAt,
    ) {
        $this->number = $number;
        $this->startsAt = $startsAt;
        $this->endsAt = $endsAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function setNumber(int $number): void
    {
        $this->number = $number;
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
}
