<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\ApiException;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractEntityService
{
    public function __construct(protected readonly EntityManagerInterface $entityManager) {}

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return list<T>
     */
    protected function listEntities(string $class): array
    {
        return $this->entityManager->getRepository($class)->findBy([], ['id' => 'ASC']);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    protected function getEntity(string $class, int $id): object
    {
        $entity = $this->entityManager->find($class, $id);

        if (!$entity instanceof $class) {
            throw ApiException::notFound();
        }

        return $entity;
    }

    protected function save(object $entity): void
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    protected function flush(): void
    {
        $this->entityManager->flush();
    }

    protected function delete(object $entity): void
    {
        try {
            $this->entityManager->remove($entity);
            $this->entityManager->flush();
        } catch (ForeignKeyConstraintViolationException) {
            throw ApiException::conflict();
        }
    }
}
