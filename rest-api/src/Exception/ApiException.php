<?php

declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;

final class ApiException extends \RuntimeException
{
    /** @param array<string, mixed> $body */
    public function __construct(
        private readonly array $body,
        private readonly int $statusCode,
    ) {
        parent::__construct('API operation failed.', $statusCode);
    }

    /** @param array<string, string> $errors */
    public static function validation(array $errors): self
    {
        return new self(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public static function notFound(): self
    {
        return new self(['error' => 'Entity not found.'], Response::HTTP_NOT_FOUND);
    }

    public static function conflict(): self
    {
        return new self(['error' => 'Entity is in use and cannot be deleted.'], Response::HTTP_CONFLICT);
    }

    /** @param array<string, mixed> $body */
    public static function http(array $body, int $statusCode): self
    {
        return new self($body, $statusCode);
    }

    /** @return array<string, mixed> */
    public function getBody(): array
    {
        return $this->body;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
