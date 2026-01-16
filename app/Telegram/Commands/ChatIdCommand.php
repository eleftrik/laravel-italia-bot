<?php

declare(strict_types=1);

namespace App\Telegram\Commands;

use App\Telegram\Enums\CommandEnum;
use SergiX44\Nutgram\Handlers\Type\Command;
use SergiX44\Nutgram\Nutgram;

/**
 * Show the chat ID.
 */
final class ChatIdCommand extends Command
{
    protected string $command = CommandEnum::ChatId->value;

    protected ?string $description = "Mostra l'ID della chat";

    public function handle(Nutgram $bot): void
    {
        $bot->sendMessage(
            text: __('telegram.messages.chat_id', ['chat_id' => $bot->chatId()]),
        );
    }
}
