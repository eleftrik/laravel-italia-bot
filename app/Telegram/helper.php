<?php

use SergiX44\Nutgram\Telegram\Types\User\User;

if (! function_exists('buildUserMention')) {
    function buildUserMention(User $user): string
    {
        return "[{$user->first_name}](tg://user?id={$user->id})";
    }
}
