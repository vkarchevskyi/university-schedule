<?php

declare(strict_types=1);

namespace App\Service\ScheduleValidation;

use App\Exception\ApiException;
use App\Resource\Admin\ScheduleValidationConflictResource;
use App\Resource\Admin\ScheduleValidationResource;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;

final readonly class HttpScheduleValidationClient implements ScheduleValidationClientInterface
{
    public function __construct(
        #[Autowire('%env(default:schedule_service_url_default:SCHEDULE_SERVICE_URL)%')]
        private string $scheduleServiceUrl,
    ) {}

    /** @param array<string, mixed> $payload */
    public function validate(array $payload): ScheduleValidationResource
    {
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $response = @file_get_contents(
            sprintf('%s/validate-schedule', rtrim($this->scheduleServiceUrl, '/')),
            false,
            stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
                    'content' => $body,
                    'ignore_errors' => true,
                    'timeout' => 5,
                ],
            ]),
        );

        $lastHeaders = http_get_last_response_headers();
        $headers = is_array($lastHeaders) ? $lastHeaders : [];

        if ($response === false) {
            throw ApiException::http(['error' => 'Schedule validation service is unavailable.'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $statusCode = $this->statusCode($headers);
        if ($statusCode < 200 || $statusCode >= 300) {
            throw ApiException::http(['error' => 'Schedule validation service rejected the request.'], Response::HTTP_BAD_GATEWAY);
        }

        $decoded = json_decode($response, true, flags: JSON_THROW_ON_ERROR);

        if (!is_array($decoded)) {
            throw ApiException::http(['error' => 'Schedule validation service returned an invalid response.'], Response::HTTP_BAD_GATEWAY);
        }

        return $this->mapResponse($decoded);
    }

    /** @param list<string> $headers */
    private function statusCode(array $headers): int
    {
        $statusLine = $headers[0] ?? '';

        if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $statusLine, $matches) !== 1) {
            return 0;
        }

        return (int) $matches[1];
    }

    /** @param array<mixed> $decoded */
    private function mapResponse(array $decoded): ScheduleValidationResource
    {
        $conflicts = [];
        $decodedConflicts = $decoded['conflicts'] ?? [];

        if (!is_array($decodedConflicts)) {
            throw ApiException::http(['error' => 'Schedule validation service returned invalid conflicts.'], Response::HTTP_BAD_GATEWAY);
        }

        foreach ($decodedConflicts as $conflict) {
            if (!is_array($conflict)) {
                throw ApiException::http(['error' => 'Schedule validation service returned invalid conflict.'], Response::HTTP_BAD_GATEWAY);
            }

            $entryIds = $conflict['entryIds'] ?? [];

            if (!is_array($entryIds)) {
                throw ApiException::http(['error' => 'Schedule validation service returned invalid conflict entry ids.'], Response::HTTP_BAD_GATEWAY);
            }

            $conflicts[] = new ScheduleValidationConflictResource(
                $this->string($conflict['type'] ?? null),
                $this->string($conflict['message'] ?? null),
                $this->intList($entryIds),
            );
        }

        return new ScheduleValidationResource((bool) ($decoded['valid'] ?? false), $conflicts);
    }

    private function string(mixed $value): string
    {
        if (!is_string($value)) {
            throw ApiException::http(['error' => 'Schedule validation service returned invalid conflict data.'], Response::HTTP_BAD_GATEWAY);
        }

        return $value;
    }

    /**
     * @param array<mixed> $values
     *
     * @return list<int>
     */
    private function intList(array $values): array
    {
        $integers = [];

        foreach ($values as $value) {
            if (is_int($value)) {
                $integers[] = $value;
                continue;
            }

            if (is_string($value) && ctype_digit($value)) {
                $integers[] = (int) $value;
                continue;
            }

            throw ApiException::http(['error' => 'Schedule validation service returned invalid conflict entry ids.'], Response::HTTP_BAD_GATEWAY);
        }

        return $integers;
    }
}
