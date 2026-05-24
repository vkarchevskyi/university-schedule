<?php

declare(strict_types=1);

namespace App\Service\ActionLog;

use App\Entity\ActionLog;
use App\Resource\Admin\ActionLogResource;
use App\Resource\Admin\ActionLogResourceMapper;
use App\Resource\Admin\ResourceCollection;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ListActionLogsService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ActionLogResourceMapper $mapper,
    ) {}

    public function list(): ResourceCollection
    {
        $logs = $this->entityManager->getRepository(ActionLog::class)->findBy([], ['createdAt' => 'DESC', 'id' => 'DESC']);

        return new ResourceCollection(array_values(array_map(
            fn(ActionLog $log): ActionLogResource => $this->mapper->map($log),
            $logs,
        )));
    }
}
