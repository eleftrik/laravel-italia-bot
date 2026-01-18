<?php

declare(strict_types=1);

use App\Telegram\Enums\CommandEnum;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Testing\FakeNutgram;
use Tests\Fixtures\Helpers\BotHelper;

describe('when sending /chatid', function (): void {
    it('replies with the chat ID', function (): void {
        /** @var FakeNutgram $bot */
        $bot = resolve(Nutgram::class);

        $botUser = BotHelper::makeBotUser();

        $chatId = 123;
        $chat = BotHelper::makeChat(id: $chatId);

        $bot->setCommonUser($botUser)
            ->setCommonChat($chat)
            ->hearText(CommandEnum::ChatId->command())
            ->willReceive(result: BotHelper::mockAdminResponse($botUser)) // mock getChatAdministrators (middleware)
            ->reply()
            ->assertReplyText("L'ID della chat è $chatId", index: 1);
    });
});
