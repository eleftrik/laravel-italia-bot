<?php

declare(strict_types=1);

namespace App\Telegram\Handlers;

use Illuminate\Support\Arr;
use Illuminate\Support\Lottery;
use Random\RandomException;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Chat\ChatPermissions;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\User\User;

final class CaptchaHandler
{
    private const EMOJIS = [
        'dog' => '🐕',
        'cat' => '🐱',
        'sun' => '☀️',
        'moon' => '🌙',
        'star' => '⭐',
        'heart' => '❤️',
        'tree' => '🌳',
        'flower' => '🌸',
        'car' => '🚗',
        'tractor' => '🚜',
        'clock' => '⏰',
        'pizza' => '🍕',
    ];

    public function __invoke(Nutgram $bot): void
    {
        $newChatMembers = $this->getNewChatMembers($bot);

        if ($newChatMembers === []) {
            return;
        }

        $chatId = $bot->chatId();

        if ($chatId === null) {
            return;
        }

        foreach ($newChatMembers as $newChatMember) {
            // Should I skip bots? Maybe no...
            /*if ($newChatMember->is_bot) {
                continue;
            }*/

            $this->muteUser($bot, $chatId, $newChatMember->id);
            $this->sendCaptchaChallenge($bot, $chatId, $newChatMember);
        }
    }

    /**
     * @return User[]
     */
    private function getNewChatMembers(Nutgram $bot): array
    {
        return $bot->message()->new_chat_members ?? [];
    }

    private function muteUser(Nutgram $bot, int $chatId, int $userId): void
    {
        $bot->restrictChatMember(
            chat_id: $chatId,
            user_id: $userId,
            permissions: new ChatPermissions(
                can_send_messages: false,
                can_send_audios: false,
                can_send_documents: false,
                can_send_photos: false,
                can_send_videos: false,
                can_send_video_notes: false,
                can_send_voice_notes: false,
                can_send_polls: false,
                can_send_other_messages: false,
                can_add_web_page_previews: false,
                can_change_info: false,
                can_invite_users: false,
                can_pin_messages: false,
                can_manage_topics: false,
            ),
        );
    }

    private function sendCaptchaChallenge(Nutgram $bot, int $chatId, User $user): void
    {
        Lottery::odds(1, 2)
            ->winner(fn () => $this->sendMathChallenge($bot, $chatId, $user))
            ->loser(fn () => $this->sendEmojiChallenge($bot, $chatId, $user))
            ->choose();
    }

    /**
     * @throws RandomException
     */
    private function sendMathChallenge(Nutgram $bot, int $chatId, User $user): void
    {
        $num1 = random_int(1, 10);
        $num2 = random_int(1, 10);
        $correctAnswer = $num1 + $num2;

        // Generate 3 wrong answers
        $answers = [$correctAnswer];
        while (count($answers) < 4) {
            $wrongAnswer = random_int(2, 20);
            if (! in_array($wrongAnswer, $answers, true)) {
                $answers[] = $wrongAnswer;
            }
        }

        /** @var array<int, int> $answers */
        $answers = Arr::shuffle($answers);

        $userMention = buildUserMention($user);
        $text = __('telegram.captcha.math_question', [
            'user' => $userMention,
            'num1' => $num1,
            'num2' => $num2,
        ]);

        $keyboard = InlineKeyboardMarkup::make();
        $buttons = [];

        foreach ($answers as $answer) {
            $isCorrect = $answer === $correctAnswer ? '1' : '0';
            $buttons[] = InlineKeyboardButton::make(
                text: (string) $answer,
                callback_data: "captcha:math:{$user->id}:{$isCorrect}",
            );
        }

        $keyboard->addRow(...$buttons);

        $bot->sendMessage(
            text: $text,
            chat_id: $chatId,
            parse_mode: ParseMode::MARKDOWN,
            reply_markup: $keyboard,
        );
    }

    /**
     * @throws RandomException
     */
    private function sendEmojiChallenge(Nutgram $bot, int $chatId, User $user): void
    {
        $emojiKeys = array_keys(self::EMOJIS);

        /** @var array<int, string> $emojiKeys */
        $emojiKeys = Arr::shuffle($emojiKeys);

        /** @var array<int, string> $selectedKeys */
        $selectedKeys = array_slice($emojiKeys, 0, 4);

        // Select one random key as the correct answer (0-3 since we always have 4 keys)
        $correctKey = $selectedKeys[random_int(0, 3)];

        $text = __('telegram.captcha.emoji_question', [
            'user' => buildUserMention($user),
            'emoji_name' => __("telegram.captcha.emojis.{$correctKey}"),
        ]);

        $keyboard = InlineKeyboardMarkup::make();
        $buttons = [];

        foreach ($selectedKeys as $key) {
            $isCorrect = $key === $correctKey ? '1' : '0';
            $buttons[] = InlineKeyboardButton::make(
                text: self::EMOJIS[$key],
                callback_data: "captcha:emoji:{$user->id}:{$isCorrect}",
            );
        }

        $keyboard->addRow(...$buttons);

        $bot->sendMessage(
            text: $text,
            chat_id: $chatId,
            parse_mode: ParseMode::MARKDOWN,
            reply_markup: $keyboard,
        );
    }
}
