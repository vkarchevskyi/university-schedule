<?php

declare(strict_types=1);

namespace App\Service\Subject;

use App\Dto\Admin\SubjectRequestDto;
use App\Entity\Subject;
use App\Resource\Admin\SubjectResource;
use App\Resource\Admin\SubjectResourceMapper;
use App\Service\AbstractEntityService;
use App\Service\InputNormalizerTrait;
use Doctrine\ORM\EntityManagerInterface;

final class CreateSubjectService extends AbstractEntityService
{
    use InputNormalizerTrait;

    public function __construct(private readonly SubjectResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function handle(SubjectRequestDto $data): SubjectResource
    {
        $subject = new Subject($this->string($data->name));
        $this->save($subject);

        return $this->mapper->map($subject);
    }
}
