<?php

declare(strict_types=1);

namespace App\Service\AcademicYear;

use App\Dto\Admin\AcademicYearRequestDto;
use App\Entity\AcademicYear;
use App\Exception\ApiException;
use App\Resource\Admin\AcademicYearResource;
use App\Resource\Admin\AcademicYearResourceMapper;
use App\Service\AbstractEntityService;
use App\Service\InputNormalizerTrait;
use Doctrine\ORM\EntityManagerInterface;

final class UpdateAcademicYearService extends AbstractEntityService
{
    use InputNormalizerTrait;

    public function __construct(private readonly AcademicYearResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function handle(int $id, AcademicYearRequestDto $data): AcademicYearResource
    {
        $academicYear = $this->getEntity(AcademicYear::class, $id);

        if ($data->has('name')) {
            $academicYear->setName($this->string($data->name));
        }
        if ($data->has('startsAt')) {
            $academicYear->setStartsAt($this->date($data->startsAt));
        }
        if ($data->has('endsAt')) {
            $academicYear->setEndsAt($this->date($data->endsAt));
        }

        $this->validateRange($academicYear);
        $this->flush();

        return $this->mapper->map($academicYear);
    }

    private function validateRange(AcademicYear $academicYear): void
    {
        if ($academicYear->getStartsAt() >= $academicYear->getEndsAt()) {
            throw ApiException::validation(['endsAt' => 'End date must be after start date.']);
        }
    }
}
