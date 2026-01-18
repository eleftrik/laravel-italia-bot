<?php

declare(strict_types=1);

use App\Telegram\Enums\CommandEnum;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Testing\FakeNutgram;
use Tests\Fixtures\Helpers\BotHelper;

describe('when sending /start', function (): void {
    it('replies with a message', function (): void {
        /** @var FakeNutgram $bot */
        $bot = resolve(Nutgram::class);

        $botUser = BotHelper::makeBotUser();

        $chat = BotHelper::makeChat();

        $bot->setCommonChat($chat)
            ->setCommonUser($botUser)
            ->hearText(CommandEnum::Start->command())
            ->willReceive(result: BotHelper::mockAdminResponse($botUser)) // mock getChatAdministrators (middleware)
            ->reply()
            ->assertReplyText('Hello, world!', 1);
    });
});
