<?php

declare(strict_types=1);

use App\Telegram\Enums\CommandEnum;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\User\User;
use SergiX44\Nutgram\Testing\FakeNutgram;
use Tests\Fixtures\Helpers\BotHelper;

describe('when sending /ban without replying to a message', function (): void {
    it('does not send any reply', function (): void {
        /** @var FakeNutgram $bot */
        $bot = resolve(Nutgram::class);

        $user = BotHelper::makeUser();

        $bot->setCommonUser($user)
            ->setCommonChat(BotHelper::makeChat())
            ->hearText(CommandEnum::Ban->command())
            ->willReceive(result: BotHelper::mockAdminResponse($user))
            ->assertNoReply();
    });
});

describe('when sending /ban replying to a user message', function (): void {
    it('bans the user if user is admin', function (): void {
        /** @var FakeNutgram $bot */
        $bot = resolve(Nutgram::class);

        $botUser = BotHelper::makeBotUser();

        $userFirstNameToBan = 'Mario';
        $userIdToBan = 42;
        $userToBan = BotHelper::makeUser(id: $userIdToBan, firstName: $userFirstNameToBan);

        $chat = BotHelper::makeChat();

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
            ->willReceive(result: BotHelper::mockAdminResponse($botUser)) // mock getChatAdministrators (middleware - user must be admin)
            ->willReceivePartial(result: [
                'status' => 'member',
                'user' => $userToBan->toArray(),
            ]) // mock getChatMember (target user is a normal member, not admin)
            ->reply()
            ->assertCalled('banChatMember')
            ->assertReplyText("🔨L'utente [$userFirstNameToBan](tg://user?id=$userIdToBan) ci ha lasciato\. Rimarrà sempre nei nostri cuori\. 🪽", 3);
    });

    it('will not ban users if user is member', function (): void {
        /** @var FakeNutgram $bot */
        $bot = resolve(Nutgram::class);

        $memberUser = BotHelper::makeUser();

        $userToBan = BotHelper::makeUser(
            id: 2,
            firstName: 'Spammer',
            username: 'spammer',
        );
        $chat = BotHelper::makeChat();

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
