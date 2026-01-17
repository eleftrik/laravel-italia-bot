<?php

declare(strict_types=1);

namespace App\Telegram\Commands;

use App\Telegram\Enums\CommandEnum;
use SergiX44\Nutgram\Handlers\Type\Command;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Chat\ChatMember;
use SergiX44\Nutgram\Telegram\Types\User\User;

final class BanUserCommand extends Command
{
    protected string $command = CommandEnum::Ban->value;

    protected ?string $description = 'Consente di bannare un utente';

    public function handle(Nutgram $bot): void
    {
        // The command must be called in reply to a user

        $reply = $bot->message()?->reply_to_message;

        if (! $reply instanceof \SergiX44\Nutgram\Telegram\Types\Message\Message) {
            return;
        }

        // Can't ban the BOT itself

        /** @var User $targetUser */
        $targetUser = $reply->from;

        if ($targetUser->id === $bot->userId()) {
            return;
        }

        // Can't ban admins

        /** @var int $chatId */
        $chatId = $bot->chatId();
        $targetMember = $bot->getChatMember($chatId, $targetUser->id);

        if (! $targetMember instanceof ChatMember) {
            return;
        }

        // Ban user and remove all their messages
        $bot->banChatMember(
            chat_id: $chatId,
            user_id: $targetUser->id,
            revoke_messages: true
        );

        $bot->sendMessage(
            text: __('telegram.messages.user_has_been_banned', ['username' => $targetUser->username])
        );
    }
}
