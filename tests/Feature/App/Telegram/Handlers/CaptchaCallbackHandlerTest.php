<?php

declare(strict_types=1);

use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Chat\Chat;
use SergiX44\Nutgram\Telegram\Types\User\User;
use SergiX44\Nutgram\Testing\FakeNutgram;
use Tests\Fixtures\Helpers\BotHelper;

function setupBotForCaptchaCallback(
    FakeNutgram $bot,
    Chat $chat,
    User $user,
    string $callbackData,
): FakeNutgram {
    return $bot->setCommonUser($user)
        ->setCommonChat($chat)
        ->hearCallbackQueryData($callbackData);
}

describe('when user answers captcha correctly', function (): void {
    it('unmutes the user', function (): void {
        /** @var FakeNutgram $bot */
        $bot = resolve(Nutgram::class);
        $user = BotHelper::makeUser(42, 'Mario', 'mario_rossi');
        $chat = BotHelper::makeChat();

        setupBotForCaptchaCallback($bot, $chat, $user, 'captcha:math:42:1')
            ->willReceive(result: true) // restrictChatMember (unmute)
            ->willReceive(result: true) // deleteMessage
            ->willReceive(result: true) // answerCallbackQuery
            ->willReceive(result: [     // sendMessage (welcome)
                'message_id' => 2,
                'chat' => $chat->toArray(),
                'date' => time(),
                'text' => 'Welcome message',
            ])
            ->reply()
            ->assertCalled('restrictChatMember');
    });

    it('deletes the captcha message', function (): void {
        /** @var FakeNutgram $bot */
        $bot = resolve(Nutgram::class);
        $user = BotHelper::makeUser(42, 'Mario', 'mario_rossi');
        $chat = BotHelper::makeChat();

        setupBotForCaptchaCallback($bot, $chat, $user, 'captcha:math:42:1')
            ->willReceive(result: true)
            ->willReceive(result: true)
            ->willReceive(result: true)
            ->willReceive(result: [
                'message_id' => 2,
                'chat' => $chat->toArray(),
                'date' => time(),
                'text' => 'Welcome message',
            ])
            ->reply()
            ->assertCalled('deleteMessage');
    });

    it('sends welcome message after correct answer', function (): void {
        /** @var FakeNutgram $bot */
        $bot = resolve(Nutgram::class);
        $user = BotHelper::makeUser(42, 'Mario', 'mario_rossi');
        $chat = BotHelper::makeChat();

        $expectedText = 'Ciao [Mario](tg://user?id=42), benvenuto/a nel gruppo *Laravel Italia*';

        setupBotForCaptchaCallback($bot, $chat, $user, 'captcha:math:42:1')
            ->willReceive(result: true)
            ->willReceive(result: true)
            ->willReceive(result: true)
            ->willReceive(result: [
                'message_id' => 2,
                'chat' => $chat->toArray(),
                'date' => time(),
                'text' => 'Welcome message',
            ])
            ->reply()
            ->assertReplyText($expectedText, index: 3);
    });

    it('sends welcome message with buttons', function (): void {
        /** @var FakeNutgram $bot */
        $bot = resolve(Nutgram::class);
        $user = BotHelper::makeUser(42, 'Mario', 'mario_rossi');
        $chat = BotHelper::makeChat();

        setupBotForCaptchaCallback($bot, $chat, $user, 'captcha:math:42:1')
            ->willReceive(result: true)
            ->willReceive(result: true)
            ->willReceive(result: true)
            ->willReceive(result: [
                'message_id' => 2,
                'chat' => $chat->toArray(),
                'date' => time(),
                'text' => 'Welcome message',
            ])
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
            }, index: 3);
    });
});

describe('when user answers captcha incorrectly', function (): void {
    it('kicks the user from the group', function (): void {
        /** @var FakeNutgram $bot */
        $bot = resolve(Nutgram::class);
        $user = BotHelper::makeUser(42, 'Mario', 'mario_rossi');
        $chat = BotHelper::makeChat();

        setupBotForCaptchaCallback($bot, $chat, $user, 'captcha:math:42:0')
            ->willReceive(result: true) // answerCallbackQuery
            ->willReceive(result: true) // deleteMessage
            ->willReceive(result: true) // banChatMember
            ->willReceive(result: true) // unbanChatMember
            ->reply()
            ->assertCalled('banChatMember');
    });

    it('unbans the user immediately so they can rejoin', function (): void {
        /** @var FakeNutgram $bot */
        $bot = resolve(Nutgram::class);
        $user = BotHelper::makeUser(42, 'Mario', 'mario_rossi');
        $chat = BotHelper::makeChat();

        setupBotForCaptchaCallback($bot, $chat, $user, 'captcha:math:42:0')
            ->willReceive(result: true)
            ->willReceive(result: true)
            ->willReceive(result: true)
            ->willReceive(result: true)
            ->reply()
            ->assertCalled('unbanChatMember');
    });

    it('deletes the captcha message', function (): void {
        /** @var FakeNutgram $bot */
        $bot = resolve(Nutgram::class);
        $user = BotHelper::makeUser(42, 'Mario', 'mario_rossi');
        $chat = BotHelper::makeChat();

        setupBotForCaptchaCallback($bot, $chat, $user, 'captcha:math:42:0')
            ->willReceive(result: true)
            ->willReceive(result: true)
            ->willReceive(result: true)
            ->willReceive(result: true)
            ->reply()
            ->assertCalled('deleteMessage');
    });

    it('does not send welcome message', function (): void {
        /** @var FakeNutgram $bot */
        $bot = resolve(Nutgram::class);
        $user = BotHelper::makeUser(42, 'Mario', 'mario_rossi');
        $chat = BotHelper::makeChat();

        setupBotForCaptchaCallback($bot, $chat, $user, 'captcha:math:42:0')
            ->willReceive(result: true)
            ->willReceive(result: true)
            ->willReceive(result: true)
            ->willReceive(result: true)
            ->reply()
            ->assertCalled('sendMessage', times: 0);
    });
});

describe('when another user tries to answer the captcha', function (): void {
    it('prevents other users from answering', function (): void {
        /** @var FakeNutgram $bot */
        $bot = resolve(Nutgram::class);
        $clickingUser = BotHelper::makeUser(99, 'Luigi', 'luigi_verdi');
        $chat = BotHelper::makeChat();

        // User 99 tries to answer the captcha for user 42
        setupBotForCaptchaCallback($bot, $chat, $clickingUser, 'captcha:math:42:1')
            ->willReceive(result: true) // answerCallbackQuery with alert
            ->reply()
            ->assertCalled('answerCallbackQuery');
    });

    it('does not unmute or kick anyone', function (): void {
        /** @var FakeNutgram $bot */
        $bot = resolve(Nutgram::class);
        $clickingUser = BotHelper::makeUser(99, 'Luigi', 'luigi_verdi');
        $chat = BotHelper::makeChat();

        setupBotForCaptchaCallback($bot, $chat, $clickingUser, 'captcha:math:42:1')
            ->willReceive(result: true)
            ->reply()
            ->assertCalled('restrictChatMember', times: 0)
            ->assertCalled('banChatMember', times: 0);
    });
});

describe('emoji captcha', function (): void {
    it('works the same as math captcha for correct answers', function (): void {
        /** @var FakeNutgram $bot */
        $bot = resolve(Nutgram::class);
        $user = BotHelper::makeUser(42, 'Mario', 'mario_rossi');
        $chat = BotHelper::makeChat();

        setupBotForCaptchaCallback($bot, $chat, $user, 'captcha:emoji:42:1')
            ->willReceive(result: true)
            ->willReceive(result: true)
            ->willReceive(result: true)
            ->willReceive(result: [
                'message_id' => 2,
                'chat' => $chat->toArray(),
                'date' => time(),
                'text' => 'Welcome message',
            ])
            ->reply()
            ->assertCalled('restrictChatMember')
            ->assertCalled('deleteMessage')
            ->assertCalled('sendMessage');
    });

    it('works the same as math captcha for wrong answers', function (): void {
        /** @var FakeNutgram $bot */
        $bot = resolve(Nutgram::class);
        $user = BotHelper::makeUser(42, 'Mario', 'mario_rossi');
        $chat = BotHelper::makeChat();

        setupBotForCaptchaCallback($bot, $chat, $user, 'captcha:emoji:42:0')
            ->willReceive(result: true)
            ->willReceive(result: true)
            ->willReceive(result: true)
            ->willReceive(result: true)
            ->reply()
            ->assertCalled('banChatMember')
            ->assertCalled('unbanChatMember');
    });
});
