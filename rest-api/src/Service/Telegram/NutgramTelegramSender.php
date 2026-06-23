<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Exception\ApiException;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use Symfony\Component\HttpFoundation\Response;

final readonly class NutgramTelegramSender implements TelegramSenderInterface
{
    public function __construct(private string $telegramBotToken) {}

    /** @param list<list<TelegramInlineButton>> $keyboard */
    public function sendMessage(int $chatId, string $text, array $keyboard = [], ?string $parseMode = null): void
    {
        $this->bot()->sendMessage(
            $text,
            $chatId,
            parse_mode: $this->parseMode($parseMode),
            reply_markup: $this->keyboard($keyboard),
        );
    }

    public function answerCallbackQuery(string $callbackQueryId, ?string $text = null): void
    {
        $this->bot()->answerCallbackQuery($callbackQueryId, $text);
    }

    private function bot(): Nutgram
    {
        if ($this->telegramBotToken === '') {
            throw ApiException::http(['error' => 'Telegram bot token is not configured.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new Nutgram($this->telegramBotToken);
    }

    /** @param list<list<TelegramInlineButton>> $keyboard */
    private function parseMode(?string $parseMode): ?ParseMode
    {
        if ($parseMode === null) {
            return null;
        }

        return match (strtoupper($parseMode)) {
            'HTML' => ParseMode::HTML,
            'MARKDOWN', 'MARKDOWNV2' => ParseMode::MARKDOWN,
            default => null,
        };
    }

    /** @param list<list<TelegramInlineButton>> $keyboard */
    private function keyboard(array $keyboard): ?InlineKeyboardMarkup
    {
        if ($keyboard === []) {
            return null;
        }

        $markup = InlineKeyboardMarkup::make();
        foreach ($keyboard as $row) {
            $buttons = array_map(
                fn(TelegramInlineButton $button): InlineKeyboardButton => InlineKeyboardButton::make($button->text, callback_data: $button->callbackData),
                $row,
            );
            $markup->addRow(...$buttons);
        }

        return $markup;
    }
}
