<?php

declare(strict_types=1);

use App\Telegram\Enums\CommandEnum;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ChatType;
use SergiX44\Nutgram\Telegram\Types\Chat\Chat;
use SergiX44\Nutgram\Telegram\Types\User\User;
use SergiX44\Nutgram\Testing\FakeNutgram;

describe('when sending /ban without replying to a message', function (): void {
    it('does not send any reply', function (): void {
        /** @var FakeNutgram $bot */
        $bot = resolve(Nutgram::class);

        $user = makeUser();

        $chatId = 123;
        $bot->setCommonUser($user)
            ->setCommonChat(Chat::make(id: $chatId, type: ChatType::GROUP))
            ->hearText(CommandEnum::Ban->command())
            ->willReceive(result: mockAdminResponse($user))
            ->assertNoReply();
    });
});

describe('when sending /ban replying to a user message', function (): void {
    it('bans the user if user is admin', function (): void {
        /** @var FakeNutgram $bot */
        $bot = resolve(Nutgram::class);

        $botUser = makeBotUser();

        $usernameToBan = 'spammer';
        $userToBan = makeUser(username: $usernameToBan);

        $chat = makeChat();

        $bot->setCommonUser($botUser)
            ->setCommonChat($chat)
            ->hearMessage([
                'text' => CommandEnum::Ban->command(),
                'reply_to_message' => [
                    'from' => $userToBan->toArray(),
                    'chat' => $chat->toArray(),
                    'text' => 'Spam message',
                ],
            ])
            ->willReceive(result: mockAdminResponse($botUser)) // mock getChatAdministrators (middleware - user must be admin)
            ->willReceivePartial(result: [
                'status' => 'member',
                'user' => $userToBan->toArray(),
            ]) // mock getChatMember (target user is a normal member, not admin)
            ->reply()
            ->assertCalled('banChatMember')
            ->assertReplyText("🔨L'utente @$usernameToBan ci ha lasciato. Rimarrà sempre nei nostri cuori. 🪽", 3);
    });

    it('will not ban users if user is member', function (): void {
        /** @var FakeNutgram $bot */
        $bot = resolve(Nutgram::class);

        $memberUser = makeUser();

        $userToBan = makeUser(
            id: 2,
            firstName: 'Spammer',
            username: 'spammer',
        );
        $chat = makeChat();

        $bot->setCommonUser($memberUser)
            ->setCommonChat($chat)
            ->hearMessage([
                'text' => CommandEnum::Ban->command(),
                'reply_to_message' => [
                    'from' => $userToBan->toArray(),
                    'chat' => $chat->toArray(),
                    'text' => 'Spam message',
                ],
            ])
            ->willReceive(result: []) // mock getChatAdministrators (middleware - returns empty, user is not admin)
            ->reply()
            ->assertCalled('banChatMember', times: 0)
            ->assertCalled('sendMessage', times: 0);
    });
});
