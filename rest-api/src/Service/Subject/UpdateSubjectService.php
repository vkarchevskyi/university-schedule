<?php

declare(strict_types=1);

namespace App\Service\Subject;

use App\Dto\Admin\SubjectRequestDto;
use App\Entity\Subject;
use App\Resource\Admin\SubjectResource;
use App\Resource\Admin\SubjectResourceMapper;
use App\Service\AbstractEntityService;
use Doctrine\ORM\EntityManagerInterface;

final class UpdateSubjectService extends AbstractEntityService
{
    public function __construct(private readonly SubjectResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function handle(int $id, SubjectRequestDto $data): SubjectResource
    {
        $subject = $this->getEntity(Subject::class, $id);

        if ($data->has('name')) {
            $subject->setName($this->string($data->name));
        }

        $this->flush();

        return $this->mapper->map($subject);
    }
}
