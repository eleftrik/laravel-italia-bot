<?php

declare(strict_types=1);

use App\Telegram\Enums\CommandEnum;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ChatType;
use SergiX44\Nutgram\Telegram\Types\Chat\Chat;
use SergiX44\Nutgram\Testing\FakeNutgram;

describe('when sending /start', function () {
    it('replies with a message', function () {
        /** @var FakeNutgram $bot */
        $bot = resolve(Nutgram::class);

        $botUser = makeBotUser();

        $chat = Chat::make(id: 1, type: ChatType::GROUP);

        $bot->setCommonChat($chat)
            ->setCommonUser($botUser)
            ->hearText(CommandEnum::Start->command())
            ->willReceive(result: [
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
            ]) // mock getChatAdministrators (middleware)
            ->reply()
            ->assertReplyText('Hello, world!', 1);
    });
});
