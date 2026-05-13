<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Admin\SubjectRequestDto;
use App\Entity\Subject;

final class SubjectService extends AbstractEntityService
{
    /** @return array{items: list<array<string, mixed>>} */
    public function list(): array
    {
        return ['items' => array_map($this->serialize(...), $this->listEntities(Subject::class))];
    }

    /** @return array<string, mixed> */
    public function get(int $id): array
    {
        return $this->serialize($this->getEntity(Subject::class, $id));
    }

    /** @return array<string, mixed> */
    public function create(SubjectRequestDto $data): array
    {
        $subject = new Subject($this->string($data->name));
        $this->save($subject);

        return $this->serialize($subject);
    }

    /** @return array<string, mixed> */
    public function update(int $id, SubjectRequestDto $data): array
    {
        $subject = $this->getEntity(Subject::class, $id);

        if ($data->has('name')) {
            $subject->setName($this->string($data->name));
        }

        $this->flush();

        return $this->serialize($subject);
    }

    public function deleteById(int $id): void
    {
        $this->delete($this->getEntity(Subject::class, $id));
    }

    /** @return array<string, mixed> */
    private function serialize(Subject $subject): array
    {
        return ['id' => $subject->getId(), 'name' => $subject->getName()];
    }
}
