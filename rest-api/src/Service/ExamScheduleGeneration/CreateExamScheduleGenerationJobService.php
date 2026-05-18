<?php

declare(strict_types=1);

namespace App\Service\ExamScheduleGeneration;

use App\Dto\Admin\ExamScheduleGenerationRequestDto;
use App\Entity\Admin;
use App\Entity\ExamScheduleGenerationJob;
use App\Entity\Semester;
use App\Exception\ApiException;
use App\Resource\Admin\ExamScheduleGenerationJobResource;
use App\Resource\Admin\ExamScheduleGenerationJobResourceMapper;
use App\Service\AbstractEntityService;
use App\Service\InputNormalizerTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class CreateExamScheduleGenerationJobService extends AbstractEntityService
{
    use InputNormalizerTrait;

    public function __construct(
        private readonly ExamScheduleGenerationJobResourceMapper $mapper,
        private readonly ExamScheduleGenerationPublisherInterface $publisher,
        private readonly Security $security,
        EntityManagerInterface $entityManager,
    ) {
        parent::__construct($entityManager);
    }

    public function handle(ExamScheduleGenerationRequestDto $data): ExamScheduleGenerationJobResource
    {
        $semester = $this->getEntity(Semester::class, $this->positiveInt($data->semesterId));
        $admin = $this->currentAdmin();
        $job = new ExamScheduleGenerationJob($this->uuid(), $semester, $admin, new \DateTimeImmutable());

        $this->entityManager->persist($job);
        $this->flush();
        $this->publisher->publish([
            'jobId' => $job->getId(),
            'semesterId' => (int) $semester->getId(),
            'requestedByAdminId' => (int) $admin->getId(),
        ]);

        return $this->mapper->map($job);
    }

    private function currentAdmin(): Admin
    {
        $user = $this->security->getUser();

        if (!$user instanceof Admin) {
            throw ApiException::http(['error' => 'Authenticated admin was not found.'], 401);
        }

        return $user;
    }

    private function uuid(): string
    {
        $bytes = bin2hex(random_bytes(16));

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($bytes, 0, 8),
            substr($bytes, 8, 4),
            substr($bytes, 12, 4),
            substr($bytes, 16, 4),
            substr($bytes, 20),
        );
    }
}
