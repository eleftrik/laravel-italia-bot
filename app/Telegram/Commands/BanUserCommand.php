<?php

declare(strict_types=1);

namespace App\Telegram\Commands;

use App\Telegram\Enums\CommandEnum;
use SergiX44\Nutgram\Handlers\Type\Command;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ChatMemberStatus;
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

        if ($reply === null) {
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

        /** @var ChatMemberStatus $targetMemberStatus */
        $targetMemberStatus = $targetMember->status;

        if (in_array($targetMemberStatus->value, ['administrator', 'creator'], true)) {
            $bot->sendMessage(__('telegram.errors.cannot_ban_an_admin'));

            return;
        }

        $bot->banChatMember($chatId, $targetUser->id);

        $bot->sendMessage(
            text: __('telegram.messages.user_has_been_banned', ['username' => $targetUser->username])
        );
    }
}
