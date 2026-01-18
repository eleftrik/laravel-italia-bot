<?php

declare(strict_types=1);

use App\Telegram\Enums\CommandEnum;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Testing\FakeNutgram;

describe('when sending /start', function (): void {
    it('replies with a message', function (): void {
        /** @var FakeNutgram $bot */
        $bot = resolve(Nutgram::class);

        $botUser = makeBotUser();

        $chat = makeChat();

        $bot->setCommonChat($chat)
            ->setCommonUser($botUser)
            ->hearText(CommandEnum::Start->command())
            ->willReceive(result: mockAdminResponse($botUser)) // mock getChatAdministrators (middleware)
            ->reply()
            ->assertReplyText('Hello, world!', 1);
    });
});
