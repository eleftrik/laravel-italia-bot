<?php

use SergiX44\Nutgram\Telegram\Properties\ChatType;
use SergiX44\Nutgram\Telegram\Types\Chat\Chat;
use SergiX44\Nutgram\Telegram\Types\User\User;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
 // ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function makeBotUser(): User
{
    return User::make(
        id: 99999,
        is_bot: true,
        first_name: 'Bot',
        username: 'botman',
    );
}

function makeUser(?int $id = null, ?string $firstName = null, ?string $username = null): User
{
    return User::make(
        id: $id ?? 1,
        is_bot: false,
        first_name: $firstName ?? 'Mario',
        username: $username ?? 'mario_rossi',
    );
}

function makeChat(int $id = 123): Chat
{
    return Chat::make(id: $id, type: ChatType::GROUP);
}

/**
 * @return array<int,array<string,array<string,mixed>|bool|string>>
 */
function mockAdminResponse(User $botUser): array
{
    return [
        [
            'status' => 'administrator',
            'user' => $botUser->toArray(),
            'can_be_edited' => true,
            'is_anonymous' => false,
            'can_manage_chat' => true,
            'can_delete_messages' => true,
            'can_manage_video_chats' => true,
            'can_restrict_members' => true,
            'can_promote_members' => true,
            'can_change_info' => true,
            'can_invite_users' => true,
        ],
    ];
}
