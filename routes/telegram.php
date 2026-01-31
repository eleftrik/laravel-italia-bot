<?php

/** @var SergiX44\Nutgram\Nutgram $bot */

use App\Telegram\Commands\BanUserCommand;
use App\Telegram\Commands\ChatIdCommand;
use App\Telegram\Enums\CommandEnum;
use App\Telegram\Handlers\CaptchaCallbackHandler;
use App\Telegram\Handlers\CaptchaHandler;
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

// Captcha handler for new members - no admin middleware required
$bot->onNewChatMembers(CaptchaHandler::class)
    ->unless(! config()->boolean('bot.captcha.enabled'));
$bot->onCallbackQueryData('captcha:{type}:{userId}:{isCorrect}', CaptchaCallbackHandler::class);

// Admin-only commands
$bot->group(function (Nutgram $bot): void {
    $bot->registerCommand(BanUserCommand::class);

    // $bot->onNewChatMembers(WelcomeMessageHandler::class);

    $bot->registerCommand(ChatIdCommand::class)
        ->unless(! app()->isProduction());

    $bot->onCommand(CommandEnum::Start->value, function (Nutgram $bot): void {
        $bot->sendMessage('Hello, world!');
    })
        ->description('The start command!')
        ->unless(! app()->isProduction());

    $bot->onCommand(CommandEnum::Start->value, function (Nutgram $bot): void {
        $bot->sendMessage('Hello, world!');
    })
        ->description('The start command!')
        ->unless(! app()->isProduction());
})->middleware(IsAdminMiddleware::class);

// $bot->onNewChatMembers(WelcomeMessageHandler::class);
