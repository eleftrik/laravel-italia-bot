<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\Request;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Chat\Chat;
use SergiX44\Nutgram\Telegram\Types\User\User;
use SergiX44\Nutgram\Testing\FakeNutgram;

/**
 * @param  array<User>  $newMembers
 *
 * @throws JsonException
 */
function setupBotWithNewMembers(FakeNutgram $bot, Chat $chat, User $botUser, array $newMembers): FakeNutgram
{
    // Use the first new member as the message sender (the one who triggers new_chat_members event)
    $messageSender = $newMembers[0] ?? $botUser;

    $setup = $bot->setCommonUser($messageSender)
        ->setCommonChat($chat)
        ->hearMessage([
            'new_chat_members' => array_map(fn (User $user) => $user->toArray(), $newMembers),
        ])
        ->willReceive(result: mockAdminResponse($messageSender)); // mock getChatAdministrators (middleware) - sender must be admin

    // Add willReceive for each sendMessage call (one per new member)
    foreach ($newMembers as $member) {
        $setup->willReceive(result: [
            'message_id' => $member->id,
            'chat' => $chat->toArray(),
            'date' => time(),
            'text' => 'Welcome message',
        ]);
    }

    return $setup;
}

describe('when a new user enters the group', function () {
    it('sends welcome message to users', function () {
        /** @var FakeNutgram $bot */
        $bot = resolve(Nutgram::class);
        $botUser = makeBotUser();
        $newUser = makeUser();
        $chat = makeChat();

        setupBotWithNewMembers($bot, $chat, $botUser, [$newUser])
            ->reply()
            ->assertCalled('sendMessage');
    });

    test('welcome message contains correct text', function () {
        /** @var FakeNutgram $bot */
        $bot = resolve(Nutgram::class);
        $botUser = makeBotUser();
        $newUser = makeUser(42, 'Mario', 'mario_rossi');
        $chat = makeChat();

        $expectedText = 'Ciao [Mario](tg://user?id=42), benvenuto/a nel gruppo *Laravel Italia*';

        setupBotWithNewMembers($bot, $chat, $botUser, [$newUser])
            ->reply()
            ->assertReplyText($expectedText, index: 1);
    });

    test('welcome message contains buttons', function () {
        /** @var FakeNutgram $bot */
        $bot = resolve(Nutgram::class);
        $botUser = makeBotUser();
        $newUser = makeUser();
        $chat = makeChat();

        setupBotWithNewMembers($bot, $chat, $botUser, [$newUser])
            ->reply()
            ->assertRaw(function (Request $request): bool {
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

                $hasDocumentationButton = false;
                $hasFreeCourseButton = false;

                foreach ($buttons as $row) {
                    foreach ($row as $button) {
                        if ($button['text'] === '📕 Documentazione' && $button['url'] === 'https://laravel.com/docs/') {
                            $hasDocumentationButton = true;
                        }
                        if ($button['text'] === '💻 Corsi gratuiti' && $button['url'] === 'https://laravelfromscratch.com') {
                            $hasFreeCourseButton = true;
                        }
                    }
                }

                return $hasDocumentationButton && $hasFreeCourseButton;
            }, index: 1);
    });
});

describe('when no one enters the group', function () {
    it('sends welcome message to multiple users joining at the same time', function () {
        /** @var FakeNutgram $bot */
        $bot = resolve(Nutgram::class);
        $botUser = makeBotUser();
        $newUser1 = makeUser(1, 'Mario', 'mario_rossi');
        $newUser2 = makeUser(2, 'Luigi', 'luigi_verdi');
        $chat = makeChat();

        setupBotWithNewMembers($bot, $chat, $botUser, [$newUser1, $newUser2])
            ->reply()
            ->assertCalled('sendMessage', times: 2);
    });

    it('does not send message when no new members', function () {
        /** @var FakeNutgram $bot */
        $bot = resolve(Nutgram::class);
        $botUser = makeBotUser();
        $chat = makeChat();

        $bot->setCommonChat($chat)
            ->hearMessage(['text' => 'Hello!'])
            ->willReceive(result: $botUser->toArray())
            ->willReceive(result: mockAdminResponse($botUser))
            ->reply()
            ->assertNoReply();
    });
});
