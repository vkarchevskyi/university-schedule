<?php

declare(strict_types=1);

namespace App\Service\Teacher;

use App\Dto\Admin\TeacherRequestDto;
use App\Entity\Teacher;
use App\Resource\Admin\TeacherResource;
use App\Resource\Admin\TeacherResourceMapper;
use App\Service\AbstractEntityService;
use Doctrine\ORM\EntityManagerInterface;

final class UpdateTeacherService extends AbstractEntityService
{
    public function __construct(private readonly TeacherResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function handle(int $id, TeacherRequestDto $data): TeacherResource
    {
        $teacher = $this->getEntity(Teacher::class, $id);

        if ($data->has('firstName')) {
            $teacher->setFirstName($this->string($data->firstName));
        }
        if ($data->has('lastName')) {
            $teacher->setLastName($this->string($data->lastName));
        }
        if ($data->has('department')) {
            $teacher->setDepartment($this->string($data->department));
        }

        $this->flush();

        return $this->mapper->map($teacher);
    }
}
