<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;

trait JsonTestAssertions
{
    /** @return array<string, mixed> */
    private function responseJson(KernelBrowser $client): array
    {
        return $this->objectPayload(json_decode($client->getResponse()->getContent() ?: '', true, flags: JSON_THROW_ON_ERROR));
    }

    /** @return array<string, mixed> */
    private function objectPayload(mixed $value): array
    {
        if (!is_array($value)) {
            throw new \RuntimeException('Expected JSON object.');
        }

        $payload = [];

        foreach ($value as $key => $item) {
            if (!is_string($key)) {
                throw new \RuntimeException('Expected JSON object with string keys.');
            }

            $payload[$key] = $item;
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return list<mixed>
     */
    private function listValue(array $payload, string $key): array
    {
        if (!array_key_exists($key, $payload) || !is_array($payload[$key])) {
            throw new \RuntimeException(sprintf('Expected "%s" to be a JSON array.', $key));
        }

        return array_values($payload[$key]);
    }

    /**
     * @param list<mixed> $items
     *
     * @return array<string, mixed>
     */
    private function objectAt(array $items, int $index): array
    {
        if (!array_key_exists($index, $items)) {
            throw new \RuntimeException(sprintf('Expected item at index %d.', $index));
        }

        return $this->objectPayload($items[$index]);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function objectValue(array $payload, string $key): array
    {
        if (!array_key_exists($key, $payload)) {
            throw new \RuntimeException(sprintf('Expected "%s" to exist.', $key));
        }

        return $this->objectPayload($payload[$key]);
    }

    /** @param array<string, mixed> $payload */
    private function intValue(array $payload, string $key): int
    {
        if (!array_key_exists($key, $payload) || !is_int($payload[$key])) {
            throw new \RuntimeException(sprintf('Expected "%s" to be an integer.', $key));
        }

        return $payload[$key];
    }

    /** @param array<string, mixed> $payload */
    private function stringValue(array $payload, string $key): string
    {
        if (!array_key_exists($key, $payload) || !is_string($payload[$key])) {
            throw new \RuntimeException(sprintf('Expected "%s" to be a string.', $key));
        }

        return $payload[$key];
    }

    /** @param array<string, mixed> $payload */
    private function boolValue(array $payload, string $key): bool
    {
        if (!array_key_exists($key, $payload) || !is_bool($payload[$key])) {
            throw new \RuntimeException(sprintf('Expected "%s" to be a boolean.', $key));
        }

        return $payload[$key];
    }
}
