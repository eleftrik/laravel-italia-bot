<?php

declare(strict_types=1);

namespace App\Telegram\Enums;

enum CommandEnum: string
{
    case Ban = 'ban';
    case ChatId = 'chatid';
    case Start = 'start';
}
