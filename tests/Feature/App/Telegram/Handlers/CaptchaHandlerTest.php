<?php

declare(strict_types=1);

use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Chat\Chat;
use SergiX44\Nutgram\Telegram\Types\User\User;
use SergiX44\Nutgram\Testing\FakeNutgram;
use Tests\Fixtures\Helpers\BotHelper;

/**
 * @param  array<User>  $newMembers
 */
function setupBotForCaptcha(FakeNutgram $bot, Chat $chat, User $messageSender, array $newMembers): FakeNutgram
{
    return $bot->setCommonUser($messageSender)
        ->setCommonChat($chat)
        ->hearMessage([
            'new_chat_members' => array_map(fn (User $user): array => $user->toArray(), $newMembers),
        ])
        ->willReceive(result: true) // restrictChatMember response
        ->willReceive(result: [      // sendMessage response
            'message_id' => 1,
            'chat' => $chat->toArray(),
            'date' => time(),
            'text' => 'Captcha message',
        ]);
}

describe('when a new user enters the group', function (): void {
    it('mutes the new user', function (): void {
        /** @var FakeNutgram $bot */
        $bot = resolve(Nutgram::class);
        $newUser = BotHelper::makeUser();
        $chat = BotHelper::makeChat();

        setupBotForCaptcha($bot, $chat, $newUser, [$newUser])
            ->reply()
            ->assertCalled('restrictChatMember');
    });

    it('sends a captcha challenge message', function (): void {
        /** @var FakeNutgram $bot */
        $bot = resolve(Nutgram::class);
        $newUser = BotHelper::makeUser();
        $chat = BotHelper::makeChat();

        setupBotForCaptcha($bot, $chat, $newUser, [$newUser])
            ->reply()
            ->assertCalled('sendMessage');
    });

    it('sends captcha with inline keyboard buttons', function (): void {
        /** @var FakeNutgram $bot */
        $bot = resolve(Nutgram::class);
        $newUser = BotHelper::makeUser();
        $chat = BotHelper::makeChat();

        setupBotForCaptcha($bot, $chat, $newUser, [$newUser])
            ->reply()
            ->assertRaw(function (GuzzleHttp\Psr7\Request $request): bool {
                $body = (string) $request->getBody();
                $data = json_decode($body, true);

                if (! is_array($data)) {
                    parse_str($body, $data);
                }

                $replyMarkup = $data['reply_markup'] ?? null;

                if ($replyMarkup === null) {
                    return false;
                }

                $keyboard = is_string($replyMarkup) ? json_decode($replyMarkup, true) : $replyMarkup;
                $buttons = $keyboard['inline_keyboard'] ?? [];

                // Should have exactly 4 buttons in the first row
                return isset($buttons[0]) && count($buttons[0]) === 4;
            }, index: 1);
    });

    //    it('skips bot users', function (): void {
    //        /** @var FakeNutgram $bot */
    //        $bot = resolve(Nutgram::class);
    //        $botUser = BotHelper::makeBotUser();
    //        $chat = BotHelper::makeChat();
    //
    //        $bot->setCommonUser($botUser)
    //            ->setCommonChat($chat)
    //            ->hearMessage([
    //                'new_chat_members' => [$botUser->toArray()],
    //            ])
    //            ->reply()
    //            ->assertNoReply();
    //    });

    it('handles multiple new users', function (): void {
        /** @var FakeNutgram $bot */
        $bot = resolve(Nutgram::class);
        $newUser1 = BotHelper::makeUser(1, 'Mario', 'mario_rossi');
        $newUser2 = BotHelper::makeUser(2, 'Luigi', 'luigi_verdi');
        $chat = BotHelper::makeChat();

        $bot->setCommonUser($newUser1)
            ->setCommonChat($chat)
            ->hearMessage([
                'new_chat_members' => [
                    $newUser1->toArray(),
                    $newUser2->toArray(),
                ],
            ])
            ->willReceive(result: true) // restrictChatMember for user 1
            ->willReceive(result: [
                'message_id' => 1,
                'chat' => $chat->toArray(),
                'date' => time(),
                'text' => 'Captcha message',
            ])
            ->willReceive(result: true) // restrictChatMember for user 2
            ->willReceive(result: [
                'message_id' => 2,
                'chat' => $chat->toArray(),
                'date' => time(),
                'text' => 'Captcha message',
            ])
            ->reply()
            ->assertCalled('restrictChatMember', times: 2)
            ->assertCalled('sendMessage', times: 2);
    });
});

describe('when no one enters the group', function (): void {
    it('does not send captcha when no new members', function (): void {
        /** @var FakeNutgram $bot */
        $bot = resolve(Nutgram::class);
        $user = BotHelper::makeUser();
        $chat = BotHelper::makeChat();

        $bot->setCommonUser($user)
            ->setCommonChat($chat)
            ->hearMessage(['text' => 'Hello!'])
            ->reply()
            ->assertNoReply();
    });
});
