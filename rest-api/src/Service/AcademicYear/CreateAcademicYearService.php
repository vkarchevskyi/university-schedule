<?php

declare(strict_types=1);

namespace App\Service\AcademicYear;

use App\Dto\Admin\AcademicYearRequestDto;
use App\Entity\AcademicYear;
use App\Exception\ApiException;
use App\Resource\Admin\AcademicYearResource;
use App\Resource\Admin\AcademicYearResourceMapper;
use App\Service\AbstractEntityService;
use Doctrine\ORM\EntityManagerInterface;

final class CreateAcademicYearService extends AbstractEntityService
{
    public function __construct(private readonly AcademicYearResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function handle(AcademicYearRequestDto $data): AcademicYearResource
    {
        $academicYear = new AcademicYear($this->string($data->name), $this->date($data->startsAt), $this->date($data->endsAt));
        $this->validateRange($academicYear);
        $this->save($academicYear);

        return $this->mapper->map($academicYear);
    }

    private function validateRange(AcademicYear $academicYear): void
    {
        if ($academicYear->getStartsAt() >= $academicYear->getEndsAt()) {
            throw ApiException::validation(['endsAt' => 'End date must be after start date.']);
        }
    }
}
