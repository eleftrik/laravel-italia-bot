<?php

declare(strict_types=1);

namespace App\Telegram\Middleware;

use SergiX44\Nutgram\Middleware\Link;
use SergiX44\Nutgram\Nutgram;

/**
 * Verify the current user is a group admin.
 */
final readonly class IsAdminMiddleware
{
    public function __invoke(Nutgram $bot, Link $next): void
    {
        if ($bot->chatId() === null) {
            return;
        }

        $messageSender = $bot->message()?->from?->id;

        if ($messageSender === null) {
            return;
        }

        $administrators = $bot->getChatAdministrators($bot->chatId());

        if ($administrators === null) {
            return;
        }

        $userIsAdmin = collect($administrators)
            ->pluck('user.id')
            ->contains($messageSender);

        if (! $userIsAdmin) {
            return;
        }

        $next($bot);
    }
}
