<?php

declare(strict_types=1);

namespace App\Telegram\Handlers;

use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\User\User;

final class WelcomeMessageHandler
{
    public function __invoke(Nutgram $bot): void
    {
        $newChatMembers = $this->getNewChatMembers($bot);

        if ($newChatMembers === []) {
            return;
        }

        foreach ($newChatMembers as $newChatMember) {
            $this->sendWelcomeMessage($bot, $newChatMember);
        }
    }

    /**
     * @return User[]
     */
    private function getNewChatMembers(Nutgram $bot): array
    {
        return $bot->message()->new_chat_members ?? [];
    }

    private function sendWelcomeMessage(Nutgram $bot, User $user): void
    {
        $bot->sendMessage(
            text: $this->buildWelcomeText($user),
            parse_mode: ParseMode::MARKDOWN,
            reply_markup: $this->buildKeyboard(),
        );
    }

    private function buildWelcomeText(User $user): string
    {
        $userMention = buildUserMention($user);

        return __('telegram.messages.welcome', [
            'user' => $userMention,
            'group' => 'Laravel Italia',
        ]);
    }

    private function buildKeyboard(): InlineKeyboardMarkup
    {
        return InlineKeyboardMarkup::make()
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
    }
}
