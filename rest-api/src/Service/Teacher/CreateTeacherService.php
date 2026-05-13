<?php

declare(strict_types=1);

namespace App\Service\Teacher;

use App\Dto\Admin\TeacherRequestDto;
use App\Entity\Teacher;
use App\Resource\Admin\TeacherResource;
use App\Resource\Admin\TeacherResourceMapper;
use App\Service\AbstractEntityService;
use Doctrine\ORM\EntityManagerInterface;

final class CreateTeacherService extends AbstractEntityService
{
    public function __construct(private readonly TeacherResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function handle(TeacherRequestDto $data): TeacherResource
    {
        $teacher = new Teacher($this->string($data->firstName), $this->string($data->lastName), $this->string($data->department));
        $this->save($teacher);

        return $this->mapper->map($teacher);
    }
}
