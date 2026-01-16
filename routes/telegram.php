<?php

/** @var SergiX44\Nutgram\Nutgram $bot */

use App\Telegram\Commands\BanUserCommand;
use App\Telegram\Commands\ChatIdCommand;
use App\Telegram\Enums\CommandEnum;
use App\Telegram\Middleware\IsAdminMiddleware;
use SergiX44\Nutgram\Nutgram;

/*
|--------------------------------------------------------------------------
| Nutgram Handlers
|--------------------------------------------------------------------------
|
| Here is where you can register telegram handlers for Nutgram. These
| handlers are loaded by the NutgramServiceProvider. Enjoy!
|
*/

$bot->group(function (Nutgram $bot) {
    $bot->onCommand(CommandEnum::Start->value, function (Nutgram $bot) {
        $bot->sendMessage('Hello, world!');
    })->description('The start command!');

    $bot->registerCommand(BanUserCommand::class);

    $bot->registerCommand(ChatIdCommand::class);
})->middleware(IsAdminMiddleware::class);
