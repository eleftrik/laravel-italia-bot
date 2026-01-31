<?php

declare(strict_types=1);

namespace App\Telegram\Handlers;

use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Chat\ChatPermissions;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\User\User;

final readonly class CaptchaCallbackHandler
{
    public function __invoke(Nutgram $bot, string $type, string $targetUserId, string $isCorrect): void
    {
        $callbackQuery = $bot->callbackQuery();

        if ($callbackQuery === null) {
            return;
        }

        $clickingUser = $callbackQuery->from;
        $chatId = $bot->chatId();
        $messageId = $callbackQuery->message?->message_id;

        if ($chatId === null || $messageId === null) {
            return;
        }

        // Only the target user can answer the captcha
        if ((string) $clickingUser->id !== $targetUserId) {
            $bot->answerCallbackQuery(
                text: __('telegram.captcha.not_your_captcha'),
                show_alert: true,
            );

            return;
        }

        if ($isCorrect === '1') {
            $this->handleCorrectAnswer($bot, $chatId, $messageId, $clickingUser);
        } else {
            $this->handleWrongAnswer($bot, $chatId, $messageId, $clickingUser);
        }
    }

    private function handleCorrectAnswer(Nutgram $bot, int $chatId, int $messageId, User $user): void
    {
        // Unmute the user
        $this->unmuteUser($bot, $chatId, $user->id);

        // Delete the captcha message
        $bot->deleteMessage(
            chat_id: $chatId,
            message_id: $messageId,
        );

        // Answer the callback
        $bot->answerCallbackQuery(
            text: __('telegram.captcha.correct_answer'),
            show_alert: false,
        );

        // Send welcome message
        $this->sendWelcomeMessage($bot, $chatId, $user);
    }

    private function handleWrongAnswer(Nutgram $bot, int $chatId, int $messageId, User $user): void
    {
        // Answer the callback first
        $bot->answerCallbackQuery(
            text: __('telegram.captcha.wrong_answer'),
            show_alert: true,
        );

        // Delete the captcha message
        $bot->deleteMessage(
            chat_id: $chatId,
            message_id: $messageId,
        );

        // Kick the user (they can rejoin later)
        $bot->banChatMember(
            chat_id: $chatId,
            user_id: $user->id,
        );

        // Unban immediately so they can rejoin
        $bot->unbanChatMember(
            chat_id: $chatId,
            user_id: $user->id,
            // only_if_banned: true,
        );
    }

    private function unmuteUser(Nutgram $bot, int $chatId, int $userId): void
    {
        $bot->restrictChatMember(
            chat_id: $chatId,
            user_id: $userId,
            permissions: new ChatPermissions(
                can_send_messages: true,
                can_send_audios: true,
                can_send_documents: true,
                can_send_photos: true,
                can_send_videos: true,
                can_send_video_notes: true,
                can_send_voice_notes: true,
                can_send_polls: true,
                can_send_other_messages: true,
                can_add_web_page_previews: true,
                can_change_info: false,
                can_invite_users: true,
                can_pin_messages: false,
                can_manage_topics: false,
            ),
        );
    }

    private function sendWelcomeMessage(Nutgram $bot, int $chatId, User $user): void
    {
        // $userMention = "[{$user->first_name}](tg://user?id={$user->id})";

        $text = __('telegram.messages.welcome', [
            'user' => buildUserMention($user),
            'group' => 'Laravel Italia',
        ]);

        $keyboard = InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make(
                    text: __('telegram.buttons.documentation'),
                    url: config()->string('bot.buttons.documentation_url'),
                )
            )
            ->addRow(
                InlineKeyboardButton::make(
                    text: __('telegram.buttons.free_courses'),
                    url: config()->string('bot.buttons.free_laravel_course'),
                )
            );

        $bot->sendMessage(
            text: $text,
            chat_id: $chatId,
            parse_mode: ParseMode::MARKDOWN,
            reply_markup: $keyboard,
        );
    }
}
