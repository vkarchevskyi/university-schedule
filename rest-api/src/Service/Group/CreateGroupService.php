<?php

declare(strict_types=1);

namespace App\Service\Group;

use App\Dto\Admin\GroupRequestDto;
use App\Entity\Group as StudentGroup;
use App\Resource\Admin\GroupResource;
use App\Resource\Admin\GroupResourceMapper;
use App\Service\AbstractEntityService;
use Doctrine\ORM\EntityManagerInterface;

final class CreateGroupService extends AbstractEntityService
{
    public function __construct(private readonly GroupResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function handle(GroupRequestDto $data): GroupResource
    {
        $group = new StudentGroup($this->string($data->name), $this->string($data->speciality), $this->positiveInt($data->course), $this->nonNegativeInt($data->studentCount));
        $this->save($group);

        return $this->mapper->map($group);
    }
}
