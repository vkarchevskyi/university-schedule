<?php

declare(strict_types=1);

namespace App\Service\AI;

use Symfony\AI\Platform\Exception\ExceptionInterface as AiExceptionInterface;
use Symfony\AI\Platform\PlatformInterface;
use Symfony\AI\Platform\StructuredOutput\PlatformSubscriber;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class ParseTelegramIntentService implements TelegramIntentParserInterface
{
    /** @var non-empty-string */
    private string $model;

    public function __construct(
        private PlatformInterface $platform,
        private BuildTelegramIntentPromptService $prompts,
        private ValidatorInterface $validator,
        string $model,
    ) {
        if ($model === '') {
            throw new \InvalidArgumentException('Gemini model must not be empty.');
        }

        $this->model = $model;
    }

    public function handle(string $text): TelegramIntent
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Kiev'));
        $result = null;

        foreach ([false, true] as $repair) {
            try {
                $result = $this->platform->invoke($this->model, $this->prompts->handle($text, $now, repair: $repair), [
                    PlatformSubscriber::RESPONSE_FORMAT => TelegramIntent::class,
                ])->asObject();

                break;
            } catch (AiExceptionInterface|\RuntimeException $exception) {
                if ($repair) {
                    throw new AiParserUnavailableException('The AI intent parser is unavailable.', previous: $exception);
                }
            }
        }

        if (!$result instanceof TelegramIntent) {
            throw new AiParserUnavailableException('The AI intent parser returned an unsupported result.');
        }

        $this->normalize($result);

        if (count($this->validator->validate($result)) > 0) {
            return TelegramIntent::unknown('Не вдалося зрозуміти запит. Уточніть групу, викладача або аудиторію.');
        }

        return $result;
    }

    private function normalize(TelegramIntent $intent): void
    {
        $intent->intent = strtolower(trim($intent->intent));
        $intent->targetType = $intent->targetType === null ? null : strtolower(trim($intent->targetType));
        $intent->targetName = $this->blankToNull($intent->targetName);
        $intent->date = $this->blankToNull($intent->date);
        $intent->weekStart = $this->blankToNull($intent->weekStart);
        $intent->range = $intent->range === null ? null : strtolower(trim($intent->range));
        $intent->clarificationQuestion = $this->blankToNull($intent->clarificationQuestion);
    }

    private function blankToNull(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
