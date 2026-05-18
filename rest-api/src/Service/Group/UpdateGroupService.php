<?php

declare(strict_types=1);

namespace App\Service\Group;

use App\Dto\Admin\GroupRequestDto;
use App\Entity\Group as StudentGroup;
use App\Resource\Admin\GroupResource;
use App\Resource\Admin\GroupResourceMapper;
use App\Service\AbstractEntityService;
use App\Service\InputNormalizerTrait;
use Doctrine\ORM\EntityManagerInterface;

final class UpdateGroupService extends AbstractEntityService
{
    use InputNormalizerTrait;

    public function __construct(private readonly GroupResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function handle(int $id, GroupRequestDto $data): GroupResource
    {
        $group = $this->getEntity(StudentGroup::class, $id);

        if ($data->has('name')) {
            $group->setName($this->string($data->name));
        }
        if ($data->has('speciality')) {
            $group->setSpeciality($this->string($data->speciality));
        }
        if ($data->has('course')) {
            $group->setCourse($this->positiveInt($data->course));
        }
        if ($data->has('studentCount')) {
            $group->setStudentCount($this->nonNegativeInt($data->studentCount));
        }

        $this->flush();

        return $this->mapper->map($group);
    }
}
