<?php

declare(strict_types=1);

namespace App\Service\TimeSlot;

use App\Dto\Admin\TimeSlotRequestDto;
use App\Entity\TimeSlot;
use App\Exception\ApiException;
use App\Resource\Admin\TimeSlotResource;
use App\Resource\Admin\TimeSlotResourceMapper;
use App\Service\AbstractEntityService;
use App\Service\InputNormalizerTrait;
use Doctrine\ORM\EntityManagerInterface;

final class UpdateTimeSlotService extends AbstractEntityService
{
    use InputNormalizerTrait;

    public function __construct(private readonly TimeSlotResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function handle(int $id, TimeSlotRequestDto $data): TimeSlotResource
    {
        $timeSlot = $this->getEntity(TimeSlot::class, $id);

        if ($data->has('number')) {
            $timeSlot->setNumber($this->positiveInt($data->number));
        }
        if ($data->has('startsAt')) {
            $timeSlot->setStartsAt($this->time($data->startsAt));
        }
        if ($data->has('endsAt')) {
            $timeSlot->setEndsAt($this->time($data->endsAt));
        }

        $this->validateRange($timeSlot);
        $this->flush();

        return $this->mapper->map($timeSlot);
    }

    private function validateRange(TimeSlot $timeSlot): void
    {
        if ($timeSlot->getStartsAt() >= $timeSlot->getEndsAt()) {
            throw ApiException::validation(['endsAt' => 'End time must be after start time.']);
        }
    }
}
