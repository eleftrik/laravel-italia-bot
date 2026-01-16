<?php

declare(strict_types=1);

namespace App\Telegram\Enums;

/**
 * Commands supported by the BOT.
 */
enum CommandEnum: string
{
    case Ban = 'ban';
    case ChatId = 'chatid';
    case Start = 'start';

    public function command(): string
    {
        return "/$this->value";
    }
}
