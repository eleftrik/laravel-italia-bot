<?php

declare(strict_types=1);

namespace Tests\Fixtures\Helpers;

use SergiX44\Nutgram\Telegram\Properties\ChatType;
use SergiX44\Nutgram\Telegram\Types\Chat\Chat;
use SergiX44\Nutgram\Telegram\Types\User\User;

final readonly class BotHelper
{
    public static function makeBotUser(): User
    {
        return User::make(
            id: 99999,
            is_bot: true,
            first_name: 'Bot',
            username: 'botman',
        );
    }

    public static function makeUser(?int $id = null, ?string $firstName = null, ?string $username = null): User
    {
        return User::make(
            id: $id ?? 1,
            is_bot: false,
            first_name: $firstName ?? 'Mario',
            username: $username ?? 'mario_rossi',
        );
    }

    public static function makeChat(int $id = 123): Chat
    {
        return Chat::make(id: $id, type: ChatType::GROUP);
    }

    /**
     * @return array<int,array<string,array<string,mixed>|bool|string>>
     */
    public static function mockAdminResponse(User $botUser): array
    {
        return [
            [
                'status' => 'administrator',
                'user' => $botUser->toArray(),
                'can_be_edited' => true,
                'is_anonymous' => false,
                'can_manage_chat' => true,
                'can_delete_messages' => true,
                'can_manage_video_chats' => true,
                'can_restrict_members' => true,
                'can_promote_members' => true,
                'can_change_info' => true,
                'can_invite_users' => true,
            ],
        ];
    }
}
